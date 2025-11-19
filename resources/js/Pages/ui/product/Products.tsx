// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '../../../Layouts/AppLayout';
import Card from '../../../Components/ui/Card';
import { Heading, Text } from '../../../Components/ui/Typography';
import RecentlyViewedSection from '../recentlyViewed/components/RecentlyViewedSection';

export default function Products() {
	const { auth } = usePage().props;
	const user = auth.user;
	const [products, setProducts] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);
	const [searchTerm, setSearchTerm] = useState('');
	const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');

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

	// Debounce search term
	useEffect(() => {
		const timer = setTimeout(() => {
			setDebouncedSearchTerm(searchTerm);
		}, 500); // 500ms delay

		return () => clearTimeout(timer);
	}, [searchTerm]);

	// Fetch products when debounced search term changes
	useEffect(() => {
		setLoading(true);
		setError(null);
		
		const requestData = {};
		if (debouncedSearchTerm.trim()) {
			requestData.search = debouncedSearchTerm.trim();
		}

		axios
			.post('/api/user/products/list', requestData)
			.then((response) => {
				if (response.data && (response.data.success || response.data.status)) {
					const data = response.data.data || [];
					setProducts(Array.isArray(data) ? data : (data.data || [])); // support pagination shape
				} else {
					setProducts([]);
				}
				setLoading(false);
			})
			.catch((err) => {
				setError('Failed to load products');
				setLoading(false);
			});
	}, [debouncedSearchTerm]);

	const renderContent = () => {
		if (loading) {
			return (
				<div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
					<div className="text-center">
						<div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading products...</div>
					</div>
				</div>
			);
		}

		if (error) {
			return (
				<div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
					<div className="text-center">
						<div className="text-lg sm:text-xl md:text-2xl font-bold text-red-600 mb-4">{error}</div>
					</div>
				</div>
			);
		}

		return (
			<div className="min-h-screen bg-gray-50">
					<div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
						<div className="text-center mb-8 sm:mb-12">
							<Heading level={1} className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl">Our Products</Heading>
							<Text className="mt-3 max-w-md mx-auto text-sm sm:text-base md:text-lg lg:text-xl sm:mt-5 md:max-w-3xl" muted>
								Discover our amazing collection of products
							</Text>
						</div>

						{/* Recently Viewed Section */}
						{!debouncedSearchTerm && (
							<RecentlyViewedSection
								limit={8}
								variant="compact"
								showViewAll={true}
							/>
						)}

						{/* Search Bar */}
						<div className="mb-6 sm:mb-8 max-w-2xl mx-auto">
							<div className="relative">
								<div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
									<svg className="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
										<path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
									</svg>
								</div>
								<input
									type="text"
									placeholder="Search products by name, description, or SKU..."
									value={searchTerm}
									onChange={(e) => setSearchTerm(e.target.value)}
									className="block w-full pl-10 pr-3 py-3 sm:py-4 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm sm:text-base"
								/>
								{searchTerm && (
									<button
										onClick={() => setSearchTerm('')}
										className="absolute inset-y-0 right-0 pr-3 flex items-center"
									>
										<svg className="h-5 w-5 text-gray-400 hover:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
											<path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
										</svg>
									</button>
								)}
							</div>
							{debouncedSearchTerm && (
								<div className="mt-2 text-sm text-gray-600 text-center">
									Searching for: <span className="font-semibold">"{debouncedSearchTerm}"</span>
									{products.length > 0 && (
										<span className="ml-2">({products.length} {products.length === 1 ? 'result' : 'results'})</span>
									)}
								</div>
							)}
						</div>

						<div className="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 md:gap-6 lg:grid-cols-3 xl:grid-cols-4">
							{products.map((product) => (
								<Card key={product.uuid || product.id} className="overflow-hidden transition-shadow hover:shadow-lg flex flex-col h-full">
									<Link href={`/product?uuid=${product.uuid || product.id}`} className="aspect-w-16 aspect-h-9 bg-gray-200 flex-shrink-0 relative cursor-pointer">
										{product.primary_image_url || product.image ? (
											<img 
												src={product.primary_image_url || product.image} 
												alt={product.name} 
												className="w-full h-40 sm:h-48 object-cover transition-transform hover:scale-105"
												loading="lazy"
												onError={(e) => {
													e.target.src = '/images/placeholder.svg';
												}}
											/>
										) : (
											<div className="w-full h-40 sm:h-48 bg-gray-200 flex items-center justify-center">
												<span className="text-gray-400 text-sm sm:text-base">No Image</span>
											</div>
										)}
										{product.discount_info && (
											<div className="absolute top-2 right-2 bg-red-600 text-white px-2 py-1 rounded text-xs font-bold">
												{product.discount_info.display_text}
											</div>
										)}
									</Link>
									<div className="p-4 sm:p-5 md:p-6 flex flex-col flex-1">
										<Link href={`/product?uuid=${product.uuid || product.id}`}>
											<Heading level={3} className="mb-2 text-lg sm:text-xl md:text-2xl line-clamp-2 hover:text-indigo-600 transition-colors cursor-pointer">{product.name}</Heading>
										</Link>
										<Text size="sm" className="mb-4 line-clamp-2 text-xs sm:text-sm flex-grow">{product.description || product.short_description}</Text>
										<div className="flex flex-col gap-3 sm:gap-4 mt-auto">
											<div className="flex flex-col">
												{product.discount_info ? (
													<>
														<span className="text-xs text-gray-400 line-through">${product.discount_info.original_price}</span>
														<span className="text-xl sm:text-2xl font-bold text-red-600 whitespace-nowrap">${product.discount_info.final_price}</span>
													</>
												) : (
													<>
														{product.sale_price ? (
															<>
																<span className="text-xs text-gray-400 line-through">${product.price}</span>
																<span className="text-xl sm:text-2xl font-bold text-red-600 whitespace-nowrap">${product.sale_price}</span>
															</>
														) : (
															<span className="text-xl sm:text-2xl font-bold text-indigo-600 whitespace-nowrap">${product.price}</span>
														)}
													</>
												)}
											</div>
										</div>
									</div>
								</Card>
							))}
						</div>

						{products.length === 0 && !loading && (
							<div className="text-center py-8 sm:py-12">
								<Text className="text-base sm:text-lg" muted>
									{debouncedSearchTerm 
										? `No products found matching "${debouncedSearchTerm}". Try a different search term.`
										: 'No products available at the moment.'}
								</Text>
							</div>
						)}
					</div>
				</div>
		);
	};

	return (
		<>
			<Head title="Products" />
			<AppLayout>
				{renderContent()}
			</AppLayout>
		</>
	);
}

