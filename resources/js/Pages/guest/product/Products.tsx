// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import UserLayout from '../../../Layouts/UserLayout';
import GuestLayout from '../../../Layouts/GuestLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';

export default function Products() {
	const { auth } = usePage().props;
	const user = auth.user;
	const [products, setProducts] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);
	const [addingToCart, setAddingToCart] = useState({});
	const [cartMessages, setCartMessages] = useState({});

	useEffect(() => {
		axios
			.post('/api/user/products/list', {})
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
	}, []);

	const handleAddToCart = (productId) => {
		setAddingToCart(prev => ({ ...prev, [productId]: true }));
		setCartMessages(prev => ({ ...prev, [productId]: '' }));

		const urlParams = new URLSearchParams(window.location.search);
		const token = urlParams.get('token') || localStorage.getItem('auth_token') || '';

		axios.post('/api/user/cart/add', {
			product_id: productId,
			quantity: 1
		}, {
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
				setCartMessages(prev => ({ ...prev, [productId]: 'Added to cart!' }));
				// Notify header to refresh cart count
				localStorage.setItem('cart_updated', Date.now().toString());
				setTimeout(() => {
					setCartMessages(prev => {
						const newMessages = { ...prev };
						delete newMessages[productId];
						return newMessages;
					});
				}, 3000);
			} else {
				setCartMessages(prev => ({ ...prev, [productId]: response.data.message || 'Failed to add to cart' }));
			}
			setAddingToCart(prev => {
				const newState = { ...prev };
				delete newState[productId];
				return newState;
			});
		})
		.catch(error => {
			console.error('Error adding to cart:', error);
			setCartMessages(prev => ({ ...prev, [productId]: error.response?.data?.message || 'Failed to add to cart' }));
			setAddingToCart(prev => {
				const newState = { ...prev };
				delete newState[productId];
				return newState;
			});
		});
	};

	const handleBuyNow = (productId) => {
		setAddingToCart(prev => ({ ...prev, [productId]: true }));
		setCartMessages(prev => ({ ...prev, [productId]: '' }));

		const urlParams = new URLSearchParams(window.location.search);
		const token = urlParams.get('token') || localStorage.getItem('auth_token') || '';

		axios.post('/api/user/cart/add', {
			product_id: productId,
			quantity: 1
		}, {
			headers: token ? {
				'Authorization': `Bearer ${token}`,
				'Content-Type': 'application/json'
			} : {
				'Content-Type': 'application/json'
			},
			withCredentials: true
		})
		.then(response => {
			setAddingToCart(prev => {
				const newState = { ...prev };
				delete newState[productId];
				return newState;
			});
			if (response.data.status || response.data.success) {
				// Notify header to refresh cart count
				localStorage.setItem('cart_updated', Date.now().toString());
				// Redirect to cart page
				const tokenParam = token ? `?token=${token}` : '';
				window.location.href = `/cart${tokenParam}`;
			} else {
				setCartMessages(prev => ({ ...prev, [productId]: response.data.message || 'Failed to add to cart' }));
			}
		})
		.catch(error => {
			console.error('Error adding to cart:', error);
			setCartMessages(prev => ({ ...prev, [productId]: error.response?.data?.message || 'Failed to add to cart' }));
			setAddingToCart(prev => {
				const newState = { ...prev };
				delete newState[productId];
				return newState;
			});
		});
	};

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

						<div className="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 md:gap-6 lg:grid-cols-3 xl:grid-cols-4">
							{products.map((product) => (
								<Card key={product.uuid || product.id} className="overflow-hidden transition-shadow hover:shadow-lg flex flex-col h-full">
									<div className="aspect-w-16 aspect-h-9 bg-gray-200 flex-shrink-0 relative">
										{product.primary_image_url || product.image ? (
											<img 
												src={product.primary_image_url || product.image} 
												alt={product.name} 
												className="w-full h-40 sm:h-48 object-cover"
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
									</div>
									<div className="p-4 sm:p-5 md:p-6 flex flex-col flex-1">
										<Heading level={3} className="mb-2 text-lg sm:text-xl md:text-2xl line-clamp-2">{product.name}</Heading>
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
											
											{cartMessages[product.id] && (
												<div className={`text-xs p-2 rounded ${cartMessages[product.id].includes('success') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
													{cartMessages[product.id]}
												</div>
											)}

											<div className="flex flex-col sm:flex-row gap-2">
												<Button 
													size="sm" 
													variant="outline"
													className="flex-1 touch-manipulation"
													onClick={() => handleAddToCart(product.id)}
													disabled={addingToCart[product.id] || !product.in_stock}
												>
													{addingToCart[product.id] ? 'Adding...' : 'Add to Cart'}
												</Button>
												<Button 
													size="sm" 
													className="flex-1 touch-manipulation"
													onClick={() => handleBuyNow(product.id)}
													disabled={addingToCart[product.id] || !product.in_stock}
												>
													{addingToCart[product.id] ? 'Adding...' : 'Buy Now'}
												</Button>
											</div>
											
											<Link href={`/product?uuid=${product.uuid || product.id}`} className="flex-shrink-0">
												<Button size="sm" variant="outline" className="w-full touch-manipulation">View Details</Button>
											</Link>
										</div>
									</div>
								</Card>
							))}
						</div>

						{products.length === 0 && (
							<div className="text-center py-8 sm:py-12">
								<Text className="text-base sm:text-lg" muted>No products available at the moment.</Text>
							</div>
						)}
					</div>
				</div>
		);
	};

	return (
		<>
			<Head title="Products" />
			{user ? (
				<UserLayout>
					{renderContent()}
				</UserLayout>
			) : (
				<GuestLayout>
					{renderContent()}
				</GuestLayout>
			)}
		</>
	);
}

