// @ts-nocheck
import React, { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import Button from './ui/Button';

export default function Navigation({ user }) {
    const [cartCount, setCartCount] = useState(0);
    // Only allow admin view for admins
    const isAdmin = user && user.name && (user.is_admin === true || user.role === 'admin');

    // Console log user type for debugging
    useEffect(() => {
        // Check localStorage and cookies for debugging
        const localToken = localStorage.getItem('auth_token');
        const cookieToken = document.cookie
            .split(';')
            .find(c => c.trim().startsWith('auth_token='));
        
        if (user) {
            if (isAdmin) {
                console.log('🔐 User Type: ADMIN', {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    role: user.role,
                    is_admin: user.is_admin,
                    has_localStorage_token: !!localToken,
                    has_cookie_token: !!cookieToken
                });
            } else {
                console.log('👤 User Type: USER', {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    role: user.role,
                    has_localStorage_token: !!localToken,
                    has_cookie_token: !!cookieToken
                });
            }
        } else {
            console.log('👋 User Type: GUEST (Not logged in)', {
                has_localStorage_token: !!localToken,
                has_cookie_token: !!cookieToken,
                localStorage_token_preview: localToken ? localToken.substring(0, 20) + '...' : null,
                cookie_token_preview: cookieToken ? cookieToken.split('=')[1]?.substring(0, 20) + '...' : null
            });
        }
    }, [user, isAdmin]);

    // Remove token from URL immediately - use localStorage/cookies only
    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('token')) {
                // Extract token and save to localStorage if not already there
                const token = url.searchParams.get('token');
                if (token && !localStorage.getItem('auth_token')) {
                    localStorage.setItem('auth_token', token);
                }
                // Remove token from URL immediately
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

	// Mobile menu state
	const [isMobileOpen, setIsMobileOpen] = useState(false);
	const mobileMenuRef = useRef(null);
	const menuButtonRef = useRef(null);

    useEffect(() => {
        if (!isAdmin) {
            localStorage.removeItem('admin_token');
        }
    }, [isAdmin]);


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
                    // Redirect without token in URL - use localStorage/cookies only
                    setTimeout(() => {
                        window.location.href = '/admin/dashboard';
                    }, 100);
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
            // Get token from localStorage/cookies only (not URL)
            let token = localStorage.getItem('auth_token') || '';
            
            // Try cookie if localStorage doesn't have it
            if (!token) {
                try {
                    const cookieToken = document.cookie
                        .split(';')
                        .find(c => c.trim().startsWith('auth_token='));
                    if (cookieToken) {
                        token = cookieToken.split('=')[1]?.trim() || '';
                    }
                } catch (e) {
                    token = '';
                }
            }

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
            // Get token from localStorage/cookies only (not URL)
            let token = localStorage.getItem('auth_token') || '';
            
            // Try cookie if localStorage doesn't have it
            if (!token) {
                try {
                    const cookieToken = document.cookie
                        .split(';')
                        .find(c => c.trim().startsWith('auth_token='));
                    if (cookieToken) {
                        token = cookieToken.split('=')[1]?.trim() || '';
                    }
                } catch (e) {
                    token = '';
                }
            }

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
						<Link href="/products" className="text-lg sm:text-xl font-bold text-primary-600 touch-manipulation">
							ShopSource
						</Link>
					</div>

					{/* Desktop nav */}
					<div className="hidden lg:flex items-center space-x-4">
						
						<Link
							href="/cart"
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
									<Link
										href="/profile"
										className="h-8 w-8 bg-primary-600 rounded-full flex items-center justify-center hover:bg-primary-700 transition-colors cursor-pointer"
									>
										<span className="text-text-inverse text-sm font-medium">
											{user.name.charAt(0).toUpperCase()}
										</span>
									</Link>
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

					{/* Mobile nav - Cart and hamburger */}
					<div className="flex lg:hidden items-center space-x-2">
						{/* Cart icon - matches desktop nav */}
						<Link
							href="/cart"
							className="relative inline-flex items-center justify-center p-2 rounded-md text-text-primary hover:text-primary-600 hover:bg-secondary-100 active:bg-secondary-200 focus:outline-none focus:ring-2 focus:ring-primary-500 touch-manipulation min-w-[44px] min-h-[44px]"
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
						{/* Hamburger menu button */}
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
					{/* Cart - matches desktop nav */}
					

					{/* User section - matches desktop nav */}
					{user && user.name ? (
						<div className="pt-2 border-t border-border-default space-y-2">
							{/* Admin Panel - matches desktop nav */}
							{isAdmin && (
								<a
									href={adminPanelUrl}
									onClick={(e) => {
										setIsMobileOpen(false);
										handleAdminPanelClick(e);
									}}
									className="block text-primary-600 hover:text-primary-700 hover:bg-primary-50 active:bg-primary-100 px-3 py-3 rounded-md text-base font-medium border border-primary-600 touch-manipulation min-h-[44px] flex items-center"
								>
									Admin Panel
								</a>
							)}
							{/* Profile - matches desktop nav */}
							<Link
								href="/profile"
								className="flex items-center px-3 py-3 hover:bg-secondary-50 active:bg-secondary-100 rounded-md touch-manipulation min-h-[44px]"
								onClick={() => setIsMobileOpen(false)}
							>
								<div className="h-9 w-9 sm:h-10 sm:w-10 bg-primary-600 rounded-full flex items-center justify-center flex-shrink-0 hover:bg-primary-700 transition-colors cursor-pointer">
									<span className="text-text-inverse text-sm font-medium">
										{user.name.charAt(0).toUpperCase()}
									</span>
								</div>
								<span className="ml-3 text-text-primary text-base font-medium truncate">
									{user.name}
								</span>
							</Link>
							{/* Logout - matches desktop nav */}
							<div className="px-3">
								<Button
									onClick={() => { setIsMobileOpen(false); handleLogout(); }}
									variant="danger"
									block
									size="sm"
								>
									Logout
								</Button>
							</div>
						</div>
					) : (
						/* Guest section - matches desktop nav */
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
