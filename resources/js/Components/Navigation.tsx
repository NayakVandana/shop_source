// @ts-nocheck
import React, { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { colors } from '../theme/tokens';

export default function Navigation({ user }) {
    // Only allow admin view for admins
    const isAdmin = user && user.name && (user.is_admin === true || user.role === 'admin');

	// Mobile menu state
	const [isMobileOpen, setIsMobileOpen] = useState(false);
	const mobileMenuRef = useRef(null);
	const menuButtonRef = useRef(null);

    useEffect(() => {
        if (!isAdmin) {
            localStorage.removeItem('admin_token');
        }
    }, [isAdmin]);

    // Always strip token from URL after initial render to avoid lingering re-auth
    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('token')) {
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

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

    // Auto-logout admin when browsing non-admin (user/guest) pages
    useEffect(() => {
        try {
            const isAdminPath = typeof window !== 'undefined' && window.location.pathname.startsWith('/admin');
            if (isAdminPath) return;

            const urlParams = new URLSearchParams(window.location.search);
            const qpToken = urlParams.get('token');
            const adminToken = localStorage.getItem('admin_token') || qpToken || '';

            if (adminToken) {
                fetch('/api/admin/logout', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'AdminToken': adminToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                }).catch(() => {});

                // Clear stored/admin URL token
                localStorage.removeItem('admin_token');

                // Remove token from URL without reload
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('token');
                    window.history.replaceState({}, '', url.toString());
                } catch (_) {}
            }
        } catch (_) {}
    }, []);

    // Optionally pass admin token in query if needed
    const adminToken = isAdmin ? localStorage.getItem('admin_token') : null;
    const adminPanelUrl = adminToken ? `/admin/dashboard?token=${adminToken}` : '/admin/dashboard';

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

        // Clear local/session data
        try {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('admin_token');
            document.cookie.split(';').forEach((c) => {
                document.cookie = c
                    .replace(/^ +/, '')
                    .replace(/=.*/, `=;expires=${new Date(0).toUTCString()};path=/`);
            });
        } catch (_) {}

        // Refresh to get latest backend state and props
        window.location.replace("/");
    };

	return (
		<nav className="bg-white shadow-lg sticky top-0 z-50" style={{ borderBottomColor: colors.surface.border }}>
			<div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
				<div className="flex justify-between h-16">
					<div className="flex items-center">
						<Link href="/" className="text-xl font-bold text-indigo-600">
							ShopSource
						</Link>
					</div>

					{/* Desktop nav */}
					<div className="hidden lg:flex items-center space-x-4">
						<Link
							href="/"
							className="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
						>
							Home
						</Link>
						<Link
							href="/products"
							className="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
						>
							Products
						</Link>
						{user && user.name ? (
							<div className="flex items-center space-x-4">
								{isAdmin && (
									<Link
										href={adminPanelUrl}
										className="text-indigo-600 hover:text-indigo-700 px-3 py-2 rounded-md text-sm font-medium border border-indigo-600"
									>
										Admin Panel
									</Link>
								)}
								<div className="flex items-center">
									<div className="h-8 w-8 bg-indigo-600 rounded-full flex items-center justify-center">
										<span className="text-white text-sm font-medium">
											{user.name.charAt(0).toUpperCase()}
										</span>
									</div>
									<span className="ml-2 text-gray-700">
										{user.name}
									</span>
								</div>
								<button
									onClick={handleLogout}
									className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm font-medium"
								>
									Logout
								</button>
							</div>
						) : (
							<div className="flex items-center space-x-2">
								<Link
									href="/login"
									className="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
								>
									Login
								</Link>
								<Link
									href="/register"
									className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium"
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
							className="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-indigo-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
				<div ref={mobileMenuRef} className="space-y-1 px-4 pt-2 pb-4 border-t border-gray-200 bg-white shadow-lg">
					<Link
						href="/"
						className="block text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-base font-medium"
						onClick={() => setIsMobileOpen(false)}
					>
						Home
					</Link>
					<Link
						href="/products"
						className="block text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-base font-medium"
						onClick={() => setIsMobileOpen(false)}
					>
						Products
					</Link>

					{user && user.name ? (
						<div className="pt-2 border-t border-gray-200">
							{isAdmin && (
								<Link
									href={adminPanelUrl}
									className="block text-indigo-600 hover:text-indigo-700 px-3 py-2 rounded-md text-base font-medium border border-indigo-600"
									onClick={() => setIsMobileOpen(false)}
								>
									Admin Panel
								</Link>
							)}
							<div className="flex items-center px-3 py-3">
								<div className="h-9 w-9 bg-indigo-600 rounded-full flex items-center justify-center">
									<span className="text-white text-sm font-medium">
										{user.name.charAt(0).toUpperCase()}
									</span>
								</div>
								<span className="ml-3 text-gray-700 text-base font-medium">
									{user.name}
								</span>
							</div>
							<div className="px-3">
								<button
									onClick={() => { setIsMobileOpen(false); handleLogout(); }}
									className="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-base font-medium"
								>
									Logout
								</button>
							</div>
						</div>
					) : (
						<div className="pt-2 border-t border-gray-200 space-y-2">
							<Link
								href="/login"
								className="block text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-base font-medium"
								onClick={() => setIsMobileOpen(false)}
							>
								Login
							</Link>
							<Link
								href="/register"
								className="block bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-base font-medium text-center"
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
