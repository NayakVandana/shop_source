// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import UserLayout from '../Layouts/UserLayout';
import GuestLayout from '../Layouts/GuestLayout';
import Card from '../Components/ui/Card';
import Button from '../Components/ui/Button';
import { Heading, Text } from '../Components/ui/Typography';

export default function Products() {
	const { auth } = usePage().props;
	const user = auth.user;
	const [products, setProducts] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);

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
								<Card key={product.uuid || product.id} className="overflow-hidden transition-shadow hover:shadow-lg">
									<div className="aspect-w-16 aspect-h-9 bg-gray-200">
										{product.image ? (
											<img 
												src={product.image} 
												alt={product.name} 
												className="w-full h-40 sm:h-48 object-cover"
												loading="lazy"
											/>
										) : (
											<div className="w-full h-40 sm:h-48 bg-gray-200 flex items-center justify-center">
												<span className="text-gray-400 text-sm sm:text-base">No Image</span>
											</div>
										)}
									</div>
									<div className="p-4 sm:p-5 md:p-6">
										<Heading level={3} className="mb-2 text-lg sm:text-xl md:text-2xl line-clamp-2">{product.name}</Heading>
										<Text size="sm" className="mb-4 line-clamp-2 text-xs sm:text-sm">{product.description}</Text>
										<div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-2">
											<span className="text-xl sm:text-2xl font-bold text-indigo-600">${product.price}</span>
											<Link href={`/product?uuid=${product.uuid || product.id}`} className="block sm:inline-block">
												<Button size="sm" className="w-full sm:w-auto touch-manipulation">View Details</Button>
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
