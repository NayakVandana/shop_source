// @ts-nocheck
import React, { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import Button from './ui/Button';

export default function Navigation({ user }) {
    const [cartCount, setCartCount] = useState(0);
    // Only allow admin view for admins
    const isAdmin = user && user.name && (user.is_admin === true || user.role === 'admin');

    // Helper function to get cart URL with token if available
    const getCartUrl = () => {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('auth_token') || '';
            return token ? `/cart?token=${token}` : '/cart';
        } catch (e) {
            return '/cart';
        }
    };

	// Mobile menu state
	const [isMobileOpen, setIsMobileOpen] = useState(false);
	const mobileMenuRef = useRef(null);
	const menuButtonRef = useRef(null);

    useEffect(() => {
        if (!isAdmin) {
            localStorage.removeItem('admin_token');
        }
    }, [isAdmin]);

    // Always strip token from URL after ensuring user is authenticated (to avoid lingering re-auth)
    // But only remove it when user is confirmed authenticated and not during navigation
    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('token')) {
                // Only remove token if user is authenticated (via cookie or user prop)
                // This prevents logout when clicking links immediately after login
                const hasCookie = document.cookie.includes('auth_token=');
                const hasLocalStorage = localStorage.getItem('auth_token');
                
                // Wait longer and ensure both cookie and localStorage are set before removing token
                // Also check that user prop is actually set (not just cookie exists)
                if (user && user.id && (hasCookie || hasLocalStorage)) {
                    // Much longer delay to ensure cookie is fully processed by server
                    // and Inertia requests can authenticate properly
                    // Also wait for any pending navigation to complete
                    setTimeout(() => {
                        // Triple-check: user prop, cookie, and localStorage all exist
                        const stillHasCookie = document.cookie.includes('auth_token=');
                        const stillHasLocalStorage = localStorage.getItem('auth_token');
                        const currentUser = user; // Capture user from closure
                        
                        // Only remove if all authentication methods are confirmed
                        if (currentUser && currentUser.id && (stillHasCookie || stillHasLocalStorage)) {
                            // Don't remove token if we're on a page that might need it
                            const currentPath = window.location.pathname;
                            const sensitivePaths = ['/cart', '/checkout', '/orders'];
                            const isSensitivePath = sensitivePaths.some(path => currentPath.startsWith(path));
                            
                            if (!isSensitivePath) {
                                url.searchParams.delete('token');
                                window.history.replaceState({}, '', url.toString());
                            }
                        }
                    }, 1000); // Increased to 1 second to ensure everything is ready
                }
            }
        } catch (_) {}
    }, [user]);

	// Close mobile menu on route change (best-effort) and on Escape
	useEffect(() => {
		const onKeyDown = (e) => {
			if (e.key === 'Escape') setIsMobileOpen(false);
		};
		document.addEventListener('keydown', onKeyDown);
		return () => document.removeEventListener('keydown', onKeyDown);
	}, []);

	// Close when clicking outside mobile panel
	useEffect(() => {
		if (!isMobileOpen) return;
		const onClick = (e) => {
			if (!mobileMenuRef.current) return;
			if (
				!mobileMenuRef.current.contains(e.target) &&
				menuButtonRef.current &&
				!menuButtonRef.current.contains(e.target)
			) {
				setIsMobileOpen(false);
			}
		};
		document.addEventListener('mousedown', onClick);
		return () => document.removeEventListener('mousedown', onClick);
	}, [isMobileOpen]);


    // Handle admin panel click - auto-login if no token
    const handleAdminPanelClick = async (e) => {
        e.preventDefault();
        
        let adminToken = localStorage.getItem('admin_token');
        
        // If no token and user is admin, auto-login
        if (!adminToken && isAdmin && user && user.id) {
            try {
                const response = await fetch('/api/admin/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ user_id: user.id })
                });
                
                const data = await response.json();
                
                if (data.status && data.data && data.data.access_token) {
                    adminToken = data.data.access_token;
                    localStorage.setItem('admin_token', adminToken);
                    window.location.href = `/admin/dashboard?token=${adminToken}`;
                } else {
                    console.error('Admin login failed:', data);
                    window.location.href = '/admin/login';
                }
            } catch (error) {
                console.error('Admin login error:', error);
                window.location.href = '/admin/login';
            }
        } else if (adminToken) {
            window.location.href = `/admin/dashboard?token=${adminToken}`;
        } else {
            window.location.href = '/admin/dashboard';
        }
    };

    // Optionally pass admin token in query if needed
    const adminToken = isAdmin ? localStorage.getItem('admin_token') : null;
    const adminPanelUrl = adminToken ? `/admin/dashboard?token=${adminToken}` : '/admin/dashboard';

    // Load cart count
    useEffect(() => {
        const loadCartCount = () => {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('auth_token') || '';

            axios.post('/api/user/cart/index', {}, {
                headers: token ? {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                } : {
                    'Content-Type': 'application/json'
                },
                withCredentials: true
            })
            .then(response => {
                if (response.data.status || response.data.success) {
                    const cart = response.data.data?.cart || response.data.data;
                    const totalItems = cart?.total_items || cart?.items?.reduce((sum, item) => sum + (item.quantity || 0), 0) || 0;
                    setCartCount(totalItems);
                }
            })
            .catch(error => {
                // Silently fail - cart might be empty
                setCartCount(0);
            });
        };

        loadCartCount();
        
        // Refresh cart count every 5 seconds
        const interval = setInterval(loadCartCount, 5000);
        
        // Also listen for cart updates from other tabs/windows
        window.addEventListener('storage', (e) => {
            if (e.key === 'cart_updated') {
                loadCartCount();
            }
        });

        return () => clearInterval(interval);
    }, []);

    // Logout handler
    const handleLogout = async () => {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const qpToken = urlParams.get('token');
            const localToken = localStorage.getItem('auth_token') || '';
            const token = qpToken || localToken || '';

            if (token) {
                await fetch('/api/user/logout', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'include',
                }).catch(() => {});
            }

            // Also try admin logout if an admin token is present
            try {
                const adminLocal = localStorage.getItem('admin_token') || '';
                const adminToken = qpToken || adminLocal || '';
                if (adminToken) {
                    await fetch('/api/admin/logout', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'AdminToken': adminToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'include',
                    }).catch(() => {});
                }
            } catch (_) {}
        } catch (_) {}

        // Clear local/session data but preserve cart_session_id cookie
        try {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('admin_token');
            
            // Preserve cart_session_id cookie to maintain cart after logout
            const cartSessionId = document.cookie
                .split(';')
                .find(c => c.trim().startsWith('cart_session_id='));
            
            // Clear all cookies except cart_session_id
            document.cookie.split(';').forEach((c) => {
                const cookieName = c.split('=')[0].trim();
                // Don't delete cart_session_id cookie
                if (cookieName !== 'cart_session_id') {
                    document.cookie = c
                        .replace(/^ +/, '')
                        .replace(/=.*/, `=;expires=${new Date(0).toUTCString()};path=/`);
                }
            });
        } catch (_) {}

        // Determine redirect URL - if on cart page, stay on cart page
        const currentPath = window.location.pathname;
        const redirectUrl = currentPath === '/cart' ? '/cart' : '/';

        // Refresh to get latest backend state and props
        window.location.replace(redirectUrl);
    };

	return (
		<nav className="bg-background shadow-lg sticky top-0 z-50 border-b border-border-default">
			<div className="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8">
				<div className="flex justify-between h-14 sm:h-16">
					<div className="flex items-center">
						<Link href="/" className="text-lg sm:text-xl font-bold text-primary-600 touch-manipulation">
							ShopSource
						</Link>
					</div>

					{/* Desktop nav */}
					<div className="hidden lg:flex items-center space-x-4">
						<Link
							href="/"
							className="text-text-primary hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium"
						>
							Home
						</Link>
						<Link
							href="/products"
							className="text-text-primary hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium"
						>
							Products
						</Link>
						<Link
							href={getCartUrl()}
							className="relative text-text-primary hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium"
						>
							<svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
							</svg>
							{cartCount > 0 && (
								<span className="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
									{cartCount > 99 ? '99+' : cartCount}
								</span>
							)}
						</Link>
						{user && user.name ? (
							<div className="flex items-center space-x-4">
								{isAdmin && (
									<a
										href={adminPanelUrl}
										onClick={handleAdminPanelClick}
										className="text-primary-600 hover:text-primary-700 px-3 py-2 rounded-md text-sm font-medium border border-primary-600"
									>
										Admin Panel
									</a>
								)}
								<div className="flex items-center">
									<div className="h-8 w-8 bg-primary-600 rounded-full flex items-center justify-center">
										<span className="text-text-inverse text-sm font-medium">
											{user.name.charAt(0).toUpperCase()}
										</span>
									</div>
									<span className="ml-2 text-text-primary">
										{user.name}
									</span>
								</div>
								<Button
									onClick={handleLogout}
									variant="danger"
									size="sm"
								>
									Logout
								</Button>
							</div>
						) : (
							<div className="flex items-center space-x-2">
								<Link
									href="/login"
									className="text-text-primary hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium"
								>
									Login
								</Link>
								<Link
									href="/register"
									className="bg-primary-600 text-text-inverse px-4 py-2 rounded-md hover:bg-primary-700 text-sm font-medium"
								>
									Register
								</Link>
							</div>
						)}
					</div>

					{/* Mobile hamburger */}
					<div className="flex lg:hidden items-center">
						<button
							ref={menuButtonRef}
							type="button"
							className="inline-flex items-center justify-center p-2 rounded-md text-text-primary hover:text-primary-600 hover:bg-secondary-100 active:bg-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500 touch-manipulation min-w-[44px] min-h-[44px]"
							aria-controls="mobile-menu"
							aria-expanded={isMobileOpen}
							onClick={() => setIsMobileOpen((v) => !v)}
						>
							<span className="sr-only">Open main menu</span>
							{!isMobileOpen ? (
								/* Menu icon */
								<svg className="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
									<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
								</svg>
							) : (
								/* X icon */
								<svg className="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
									<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
								</svg>
							)}
						</button>
					</div>
				</div>
			</div>

			{/* Mobile menu panel */}
			<div id="mobile-menu" className={`${isMobileOpen ? 'block' : 'hidden'} lg:hidden`}>
				<div ref={mobileMenuRef} className="space-y-1 px-3 sm:px-4 pt-2 pb-4 border-t border-border-default bg-background shadow-lg">
					<Link
						href="/"
						className="block text-text-primary hover:text-primary-600 hover:bg-secondary-50 active:bg-secondary-100 px-3 py-3 rounded-md text-base font-medium touch-manipulation min-h-[44px] flex items-center"
						onClick={() => setIsMobileOpen(false)}
					>
						Home
					</Link>
					<Link
						href="/products"
						className="block text-text-primary hover:text-primary-600 hover:bg-secondary-50 active:bg-secondary-100 px-3 py-3 rounded-md text-base font-medium touch-manipulation min-h-[44px] flex items-center"
						onClick={() => setIsMobileOpen(false)}
					>
						Products
					</Link>
					<Link
						href={getCartUrl()}
						className="relative block text-text-primary hover:text-primary-600 hover:bg-secondary-50 active:bg-secondary-100 px-3 py-3 rounded-md text-base font-medium touch-manipulation min-h-[44px] flex items-center"
						onClick={() => setIsMobileOpen(false)}
					>
						<svg className="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
						</svg>
						Cart
						{cartCount > 0 && (
							<span className="ml-2 bg-red-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
								{cartCount > 99 ? '99+' : cartCount}
							</span>
						)}
					</Link>

					{user && user.name ? (
						<div className="pt-2 border-t border-border-default">
							{isAdmin && (
								<a
									href={adminPanelUrl}
									onClick={(e) => {
										setIsMobileOpen(false);
										handleAdminPanelClick(e);
									}}
									className="block text-primary-600 hover:text-primary-700 hover:bg-primary-50 active:bg-primary-100 px-3 py-3 rounded-md text-base font-medium border border-primary-600 touch-manipulation min-h-[44px] flex items-center mb-2"
								>
									Admin Panel
								</a>
							)}
							<div className="flex items-center px-3 py-3">
								<div className="h-9 w-9 sm:h-10 sm:w-10 bg-primary-600 rounded-full flex items-center justify-center flex-shrink-0">
									<span className="text-text-inverse text-sm font-medium">
										{user.name.charAt(0).toUpperCase()}
									</span>
								</div>
								<span className="ml-3 text-text-primary text-base font-medium truncate">
									{user.name}
								</span>
							</div>
							<div className="px-3 pb-2">
								<Button
									onClick={() => { setIsMobileOpen(false); handleLogout(); }}
									variant="danger"
									block
								>
									Logout
								</Button>
							</div>
						</div>
					) : (
						<div className="pt-2 border-t border-border-default space-y-2">
							<Link
								href="/login"
								className="block text-text-primary hover:text-primary-600 hover:bg-secondary-50 active:bg-secondary-100 px-3 py-3 rounded-md text-base font-medium touch-manipulation min-h-[44px] flex items-center"
								onClick={() => setIsMobileOpen(false)}
							>
								Login
							</Link>
							<Link
								href="/register"
								className="block bg-primary-600 text-text-inverse px-4 py-3 rounded-md hover:bg-primary-700 active:bg-primary-800 text-base font-medium text-center transition-colors touch-manipulation min-h-[44px] flex items-center justify-center"
								onClick={() => setIsMobileOpen(false)}
							>
								Register
							</Link>
						</div>
					)}
				</div>
			</div>
		</nav>
	);
}
