// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';
import FormInput from '../../../Components/FormInputs/FormInput';
import FormTextarea from '../../../Components/FormInputs/FormTextarea';
import FormSelect from '../../../Components/FormInputs/FormSelect';
import FormCheckbox from '../../../Components/FormInputs/FormCheckbox';

export default function ProductForm() {
    const [loading, setLoading] = useState(false);
    const [categories, setCategories] = useState([]);
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState('');
    
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        short_description: '',
        price: '',
        sale_price: '',
        category_id: '',
        stock_quantity: '0',
        manage_stock: true,
        in_stock: true,
        is_featured: false,
        is_active: true,
        weight: '',
        dimensions: '',
    });
    
    const [imagePreview, setImagePreview] = useState(null);
    const [imagesPreview, setImagesPreview] = useState([]);
    const [videoPreview, setVideoPreview] = useState(null);
    const [videosPreview, setVideosPreview] = useState([]);
    const [selectedColors, setSelectedColors] = useState([]);
    const [availableSizes, setAvailableSizes] = useState([]);
    const [colorSizes, setColorSizes] = useState({}); // { color: [sizes] }
    const [colorImages, setColorImages] = useState({}); // { color: [files] }
    const [colorImagesPreview, setColorImagesPreview] = useState({}); // { color: [previewUrls] }
    const [colorVideos, setColorVideos] = useState({}); // { color: [files] }
    const [colorVideosPreview, setColorVideosPreview] = useState({}); // { color: [previewUrls] }
    const [colorMainImage, setColorMainImage] = useState({}); // { color: file }
    const [colorMainImagePreview, setColorMainImagePreview] = useState({}); // { color: previewUrl }
    const [colorMainVideo, setColorMainVideo] = useState({}); // { color: file }
    const [colorMainVideoPreview, setColorMainVideoPreview] = useState({}); // { color: previewUrl }
    const [variationStock, setVariationStock] = useState({}); // { "size_color": stockQuantity }
    
    const isEdit = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('id');
    const productId = typeof window !== 'undefined' 
        ? new URLSearchParams(window.location.search).get('id') 
        : null;

    useEffect(() => {
        loadCategories();
        if (isEdit && productId) {
            loadProduct(productId);
        }
    }, []);

    const loadCategories = async () => {
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/categories/list', { per_page: 100 }, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                setCategories(res.data.data?.data || res.data.data || []);
            }
        } catch (err) {
            console.error('Failed to load categories:', err);
        }
    };

    const loadProduct = async (id: string) => {
        setLoading(true);
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/products/show', { id }, {
                headers: { AdminToken: token }
            });
                if (res.data && res.data.status) {
                const product = res.data.data;
                setFormData({
                    name: product.name || '',
                    description: product.description || '',
                    short_description: product.short_description || '',
                    price: product.price || '',
                    sale_price: product.sale_price || '',
                    category_id: product.category_id || '',
                    stock_quantity: product.stock_quantity || '0',
                    manage_stock: product.manage_stock !== undefined ? product.manage_stock : true,
                    in_stock: product.in_stock !== undefined ? product.in_stock : true,
                    is_featured: product.is_featured || false,
                    is_active: product.is_active !== undefined ? product.is_active : true,
                    weight: product.weight || '',
                    dimensions: product.dimensions || '',
                });
                
                // Load colors
                if (product.colors && Array.isArray(product.colors)) {
                    setSelectedColors(product.colors);
                }
                
                // Load sizes per color from variations
                if (product.variations && Array.isArray(product.variations)) {
                    const stockData = {};
                    const sizesPerColor = {};
                    
                    product.variations.forEach(variation => {
                        const key = `${variation.size}_${variation.color}`;
                        stockData[key] = variation.stock_quantity || 0;
                        
                        // Group sizes by color
                        if (variation.color) {
                            if (!sizesPerColor[variation.color]) {
                                sizesPerColor[variation.color] = [];
                            }
                            if (!sizesPerColor[variation.color].includes(variation.size)) {
                                sizesPerColor[variation.color].push(variation.size);
                            }
                        }
                    });
                    
                    setVariationStock(stockData);
                    setColorSizes(sizesPerColor);
                } else if (product.sizes && Array.isArray(product.sizes)) {
                    // Fallback: if no variations, use product sizes for all colors
                    const sizesPerColor = {};
                    if (product.colors && Array.isArray(product.colors)) {
                        product.colors.forEach(color => {
                            sizesPerColor[color] = [...product.sizes];
                        });
                    }
                    setColorSizes(sizesPerColor);
                }
                
                // Load existing images
                if (product.media) {
                    const allImages = product.media.filter(m => m.type === 'image');
                    const videos = product.media.filter(m => m.type === 'video');
                    
                    // Separate general images (without color) and color-specific images
                    const generalImages = allImages.filter(img => !img.color || img.color === null);
                    const colorSpecificImages = {};
                    const colorMainImages = {};
                    
                    allImages.forEach(img => {
                        if (img.color) {
                            // Check if this is the main image (is_primary)
                            if (img.is_primary) {
                                colorMainImages[img.color] = img.url || (img.file_path.startsWith('http') ? img.file_path : `/storage/${img.file_path}`);
                            } else {
                                // Additional images
                                if (!colorSpecificImages[img.color]) {
                                    colorSpecificImages[img.color] = [];
                                }
                                colorSpecificImages[img.color].push(img.url || (img.file_path.startsWith('http') ? img.file_path : `/storage/${img.file_path}`));
                            }
                        }
                    });
                    
                    // Set general images
                    if (generalImages.length > 0) {
                        const imagePreviews = generalImages.map(img => img.url || (img.file_path.startsWith('http') ? img.file_path : `/storage/${img.file_path}`));
                        setImagesPreview(imagePreviews);
                        setImagePreview(imagePreviews[0]);
                    }
                    
                    // Set color-specific main images
                    if (Object.keys(colorMainImages).length > 0) {
                        setColorMainImagePreview(colorMainImages);
                    }
                    
                    // Set color-specific image previews
                    if (Object.keys(colorSpecificImages).length > 0) {
                        setColorImagesPreview(colorSpecificImages);
                    }
                    
                    // Separate general videos (without color) and color-specific videos
                    const generalVideos = videos.filter(vid => !vid.color || vid.color === null);
                    const colorSpecificVideos = {};
                    const colorMainVideos = {};
                    
                    videos.forEach(vid => {
                        if (vid.color) {
                            // Check if this is the main video (is_primary)
                            if (vid.is_primary) {
                                colorMainVideos[vid.color] = vid.url || (vid.file_path.startsWith('http') ? vid.file_path : `/storage/${vid.file_path}`);
                            } else {
                                // Additional videos
                                if (!colorSpecificVideos[vid.color]) {
                                    colorSpecificVideos[vid.color] = [];
                                }
                                colorSpecificVideos[vid.color].push(vid.url || (vid.file_path.startsWith('http') ? vid.file_path : `/storage/${vid.file_path}`));
                            }
                        }
                    });
                    
                    // Set general videos
                    if (generalVideos.length > 0) {
                        const videoPreviews = generalVideos.map(vid => vid.url || (vid.file_path.startsWith('http') ? vid.file_path : `/storage/${vid.file_path}`));
                        setVideosPreview(videoPreviews);
                        setVideoPreview(videoPreviews[0]);
                    }
                    
                    // Set color-specific main videos
                    if (Object.keys(colorMainVideos).length > 0) {
                        setColorMainVideoPreview(colorMainVideos);
                    }
                    
                    // Set color-specific video previews
                    if (Object.keys(colorSpecificVideos).length > 0) {
                        setColorVideosPreview(colorSpecificVideos);
                    }
                } else if (product.images && product.images.length > 0) {
                    // Fallback for legacy format
                    const previews = product.images.map(img => 
                        img.startsWith('http') ? img : `/storage/${img}`
                    );
                    setImagesPreview(previews);
                    setImagePreview(previews[0]);
                }
            }
        } catch (err) {
            alert('Failed to load product');
        } finally {
            setLoading(false);
        }
    };

    // Remove token from URL immediately - use localStorage/cookies only
    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('token')) {
                // Extract token and save to localStorage if not already there
                const token = url.searchParams.get('token');
                if (token && !localStorage.getItem('admin_token')) {
                    localStorage.setItem('admin_token', token);
                }
                // Remove token from URL immediately
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

    const getToken = () => {
        // Get token from localStorage/cookies only (not URL)
        return localStorage.getItem('admin_token') || '';
    };

    // Get available sizes based on category name
    const getSizesByCategory = (categoryName) => {
        if (!categoryName) {
            return [];
        }

        const text = categoryName.toLowerCase();

        // Kids sizes
        if (text.includes('kid') || text.includes('child') || text.includes('toddler')) {
            return ['2T', '3T', '4T', '5T', '6T', 'XS', 'S', 'M', 'L', 'XL', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '16'];
        }
        
        // Women sizes
        if (text.includes('women') || text.includes('woman') || text.includes('ladies')) {
            return ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24'];
        }
        
        // Men sizes
        if (text.includes('men') || text.includes('man') || text.includes('gentlemen')) {
            return ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52'];
        }
        
        // Default sizes for generic clothing
        return ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    };

    // Check if category is clothing-related
    const isClothingCategory = (categoryName) => {
        if (!categoryName) return false;
        const text = categoryName.toLowerCase();
        return text.includes('kid') || text.includes('child') || text.includes('toddler') ||
               text.includes('women') || text.includes('woman') || text.includes('ladies') ||
               text.includes('men') || text.includes('man') || text.includes('gentlemen') ||
               text.includes('clothing') || text.includes('apparel') || text.includes('wear');
    };

    // Update available sizes when category changes
    useEffect(() => {
        if (formData.category_id && categories.length > 0) {
            const selectedCategory = categories.find(cat => cat.id == formData.category_id);
            if (selectedCategory) {
                const sizes = getSizesByCategory(selectedCategory.name);
                setAvailableSizes(sizes);
            }
        } else {
            setAvailableSizes([]);
        }
    }, [formData.category_id, categories.length]);


    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
        
        
        // Clear field error when user starts typing
        if (errors[name]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }
        // Clear general error when user makes changes
        if (generalError) {
            setGeneralError('');
        }
    };

    const handleSizeToggle = (color, size) => {
        setColorSizes(prev => {
            const currentSizes = prev[color] || [];
            if (currentSizes.includes(size)) {
                // Remove size from this color
                const newSizes = currentSizes.filter(s => s !== size);
                const updated = { ...prev, [color]: newSizes };
                
                // Remove stock entries for this size-color combination
                const newStock = { ...variationStock };
                const key = `${size}_${color}`;
                delete newStock[key];
                setVariationStock(newStock);
                
                return updated;
            } else {
                // Add size to this color
                return { ...prev, [color]: [...currentSizes, size] };
            }
        });
    };

    const handleColorAdd = (e) => {
        if (e.key === 'Enter' && e.target.value.trim()) {
            const color = e.target.value.trim();
            if (!selectedColors.includes(color)) {
                setSelectedColors(prev => [...prev, color]);
                // Initialize sizes for new color with all available sizes
                if (availableSizes.length > 0) {
                    setColorSizes(prev => ({
                        ...prev,
                        [color]: [...availableSizes]
                    }));
                }
            }
            e.target.value = '';
        }
    };

    const handleColorRemove = (color) => {
        setSelectedColors(prev => prev.filter(c => c !== color));
        // Remove color images when color is removed
        setColorImages(prev => {
            const newState = { ...prev };
            delete newState[color];
            return newState;
        });
        setColorImagesPreview(prev => {
            const newState = { ...prev };
            delete newState[color];
            return newState;
        });
        // Remove color videos when color is removed
        setColorVideos(prev => {
            const newState = { ...prev };
            delete newState[color];
            return newState;
        });
        setColorVideosPreview(prev => {
            const newState = { ...prev };
            delete newState[color];
            return newState;
        });
        
        // Remove sizes for this color
        const newColorSizes = { ...colorSizes };
        delete newColorSizes[color];
        setColorSizes(newColorSizes);
        
        // Remove stock entries for this color
        const newStock = { ...variationStock };
        const sizesForColor = colorSizes[color] || [];
        sizesForColor.forEach(size => {
            const key = `${size}_${color}`;
            delete newStock[key];
        });
        setVariationStock(newStock);
        
        // Remove main image and video for this color
        const newMainImage = { ...colorMainImage };
        const newMainImagePreview = { ...colorMainImagePreview };
        const newMainVideo = { ...colorMainVideo };
        const newMainVideoPreview = { ...colorMainVideoPreview };
        delete newMainImage[color];
        delete newMainImagePreview[color];
        delete newMainVideo[color];
        delete newMainVideoPreview[color];
        setColorMainImage(newMainImage);
        setColorMainImagePreview(newMainImagePreview);
        setColorMainVideo(newMainVideo);
        setColorMainVideoPreview(newMainVideoPreview);
    };
    
    const handleStockChange = (size, color, value) => {
        const key = `${size}_${color}`;
        const newStock = {
            ...variationStock,
            [key]: parseInt(value) || 0
        };
        setVariationStock(newStock);
        
        // Auto-calculate and update general stock quantity
        const totalStock = Object.values(newStock).reduce((sum, stock) => sum + (parseInt(stock) || 0), 0);
        setFormData(prev => ({
            ...prev,
            stock_quantity: totalStock.toString()
        }));
    };

    // Calculate total stock from all variations
    const calculateTotalStock = () => {
        return Object.values(variationStock).reduce((sum, stock) => sum + (parseInt(stock) || 0), 0);
    };

    // Set default stock for all size-color combinations
    const handleSetDefaultStock = (color, defaultStock) => {
        const sizes = colorSizes[color] || [];
        const newStock = { ...variationStock };
        
        sizes.forEach(size => {
            const key = `${size}_${color}`;
            newStock[key] = parseInt(defaultStock) || 0;
        });
        
        setVariationStock(newStock);
        
        // Update general stock
        const totalStock = Object.values(newStock).reduce((sum, stock) => sum + (parseInt(stock) || 0), 0);
        setFormData(prev => ({
            ...prev,
            stock_quantity: totalStock.toString()
        }));
    };

    // Auto-sync general stock when variations change
    useEffect(() => {
        if (selectedColors.length > 0 && Object.keys(variationStock).length > 0) {
            const totalStock = Object.values(variationStock).reduce((sum, stock) => sum + (parseInt(stock) || 0), 0);
            setFormData(prev => {
                // Only update if the calculated value is different to avoid unnecessary updates
                if (prev.stock_quantity !== totalStock.toString()) {
                    return {
                        ...prev,
                        stock_quantity: totalStock.toString()
                    };
                }
                return prev;
            });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [variationStock, selectedColors.length]);

    const handleColorMainImageChange = (color, e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        
        if (!file.type.startsWith('image/')) {
            setErrors(prev => ({ ...prev, [`color_main_image_${color}`]: 'Please select a valid image file' }));
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            setErrors(prev => ({ ...prev, [`color_main_image_${color}`]: 'Image must be less than 5MB' }));
            return;
        }
        
        if (errors[`color_main_image_${color}`]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[`color_main_image_${color}`];
                return newErrors;
            });
        }
        
        const preview = URL.createObjectURL(file);
        setColorMainImage(prev => ({ ...prev, [color]: file }));
        setColorMainImagePreview(prev => ({ ...prev, [color]: preview }));
    };

    const handleColorMainVideoChange = (color, e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        
        const validVideoTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/webm'];
        const isValid = validVideoTypes.includes(file.type) || file.name.match(/\.(mp4|avi|mov|wmv|flv|webm)$/i);
        
        if (!isValid) {
            setErrors(prev => ({ ...prev, [`color_main_video_${color}`]: 'Please select a valid video file (MP4, AVI, MOV, WMV, FLV, or WEBM)' }));
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) {
            setErrors(prev => ({ ...prev, [`color_main_video_${color}`]: 'Video must be less than 10MB' }));
            return;
        }
        
        if (errors[`color_main_video_${color}`]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[`color_main_video_${color}`];
                return newErrors;
            });
        }
        
        const preview = URL.createObjectURL(file);
        setColorMainVideo(prev => ({ ...prev, [color]: file }));
        setColorMainVideoPreview(prev => ({ ...prev, [color]: preview }));
    };

    const handleColorImageChange = (color, e) => {
        const files = Array.from(e.target.files || []);
        const imageFiles = files.filter(f => f.type.startsWith('image/'));
        
        if (imageFiles.length !== files.length) {
            setErrors(prev => ({ ...prev, [`color_images_${color}`]: 'Please select only valid image files' }));
            return;
        }
        
        const oversizedFiles = imageFiles.filter(f => f.size > 5 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
            setErrors(prev => ({ ...prev, [`color_images_${color}`]: 'All images must be less than 5MB each' }));
            return;
        }
        
        if (errors[`color_images_${color}`]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[`color_images_${color}`];
                return newErrors;
            });
        }
        
        const previews = imageFiles.map(f => URL.createObjectURL(f));
        setColorImages(prev => ({ ...prev, [color]: imageFiles }));
        setColorImagesPreview(prev => ({ ...prev, [color]: previews }));
    };

    const handleColorVideoChange = (color, e) => {
        const files = Array.from(e.target.files || []);
        const validVideoTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/webm'];
        const videoFiles = files.filter(f => 
            validVideoTypes.includes(f.type) || f.name.match(/\.(mp4|avi|mov|wmv|flv|webm)$/i)
        );
        
        if (videoFiles.length !== files.length) {
            setErrors(prev => ({ ...prev, [`color_videos_${color}`]: 'Please select only valid video files (MP4, AVI, MOV, WMV, FLV, or WEBM)' }));
            return;
        }
        
        const oversizedFiles = videoFiles.filter(f => f.size > 10 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
            setErrors(prev => ({ ...prev, [`color_videos_${color}`]: 'All videos must be less than 10MB each' }));
            return;
        }
        
        if (errors[`color_videos_${color}`]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[`color_videos_${color}`];
                return newErrors;
            });
        }
        
        const previews = videoFiles.map(f => URL.createObjectURL(f));
        setColorVideos(prev => ({ ...prev, [color]: videoFiles }));
        setColorVideosPreview(prev => ({ ...prev, [color]: previews }));
    };

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                setErrors(prev => ({ ...prev, image: 'Please select a valid image file' }));
                return;
            }
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                setErrors(prev => ({ ...prev, image: 'Image size must be less than 5MB' }));
                return;
            }
            // Clear error if validation passes
            if (errors.image) {
                setErrors(prev => {
                    const newErrors = { ...prev };
                    delete newErrors.image;
                    return newErrors;
                });
            }
            setImagePreview(URL.createObjectURL(file));
            setFormData(prev => ({ ...prev, image: file }));
        }
    };

    const handleImagesChange = (e) => {
        const files = Array.from(e.target.files || []);
        const imageFiles = files.filter(f => f.type.startsWith('image/'));
        
        // Validate all files are images
        if (imageFiles.length !== files.length) {
            setErrors(prev => ({ ...prev, images: 'Please select only valid image files' }));
            return;
        }
        
        // Validate file sizes (5MB max per file)
        const oversizedFiles = imageFiles.filter(f => f.size > 5 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
            setErrors(prev => ({ ...prev, images: 'All images must be less than 5MB each' }));
            return;
        }
        
        // Clear error if validation passes
        if (errors.images) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors.images;
                return newErrors;
            });
        }
        
        const previews = imageFiles.map(f => URL.createObjectURL(f));
        setImagesPreview(previews);
        setFormData(prev => ({ ...prev, images: imageFiles }));
    };

    const handleVideoChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validate file type
            const validVideoTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/webm'];
            if (!validVideoTypes.includes(file.type) && !file.name.match(/\.(mp4|avi|mov|wmv|flv|webm)$/i)) {
                setErrors(prev => ({ ...prev, video: 'Please select a valid video file (MP4, AVI, MOV, WMV, FLV, or WEBM)' }));
                return;
            }
            // Validate file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                setErrors(prev => ({ ...prev, video: 'Video size must be less than 10MB' }));
                return;
            }
            // Clear error if validation passes
            if (errors.video) {
                setErrors(prev => {
                    const newErrors = { ...prev };
                    delete newErrors.video;
                    return newErrors;
                });
            }
            setVideoPreview(URL.createObjectURL(file));
            setFormData(prev => ({ ...prev, video: file }));
        }
    };

    const handleVideosChange = (e) => {
        const files = Array.from(e.target.files || []);
        const validVideoTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/webm'];
        const videoFiles = files.filter(f => 
            validVideoTypes.includes(f.type) || f.name.match(/\.(mp4|avi|mov|wmv|flv|webm)$/i)
        );
        
        // Validate all files are videos
        if (videoFiles.length !== files.length) {
            setErrors(prev => ({ ...prev, videos: 'Please select only valid video files (MP4, AVI, MOV, WMV, FLV, or WEBM)' }));
            return;
        }
        
        // Validate file sizes (10MB max per file)
        const oversizedFiles = videoFiles.filter(f => f.size > 10 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
            setErrors(prev => ({ ...prev, videos: 'All videos must be less than 10MB each' }));
            return;
        }
        
        // Clear error if validation passes
        if (errors.videos) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors.videos;
                return newErrors;
            });
        }
        
        const previews = videoFiles.map(f => URL.createObjectURL(f));
        setVideosPreview(previews);
        setFormData(prev => ({ ...prev, videos: videoFiles }));
    };

    const validateForm = () => {
        const validationErrors = {};
        
        // Required field validations
        if (!formData.name || (typeof formData.name === 'string' && formData.name.trim() === '')) {
            validationErrors.name = 'Product name is required.';
        }
        
        if (!formData.price || formData.price === '' || formData.price === null || formData.price === undefined) {
            validationErrors.price = 'Price is required.';
        } else {
            const price = parseFloat(formData.price);
            if (isNaN(price) || price <= 0) {
                validationErrors.price = 'Price must be a valid number greater than 0.';
            }
        }
        
        if (!formData.category_id || formData.category_id === '' || formData.category_id === null || formData.category_id === undefined) {
            validationErrors.category_id = 'Category is required.';
        }
        
        if (formData.stock_quantity === '' || formData.stock_quantity === null || formData.stock_quantity === undefined) {
            validationErrors.stock_quantity = 'Stock quantity is required.';
        } else {
            const stockQty = parseInt(formData.stock_quantity);
            if (isNaN(stockQty) || stockQty < 0) {
                validationErrors.stock_quantity = 'Stock quantity must be a valid number (0 or greater).';
            }
        }
        
        // Validate sale_price if provided (must be less than price)
        if (formData.sale_price && formData.sale_price !== '' && formData.sale_price !== null && formData.sale_price !== undefined) {
            const salePrice = parseFloat(formData.sale_price);
            const price = parseFloat(formData.price);
            if (isNaN(salePrice) || salePrice < 0) {
                validationErrors.sale_price = 'Sale price must be a valid number greater than or equal to 0.';
            } else if (price && !isNaN(price) && salePrice >= price) {
                validationErrors.sale_price = 'Sale price must be less than the regular price.';
            }
        }
        
        // Validate weight if provided
        if (formData.weight && formData.weight !== '' && formData.weight !== null && formData.weight !== undefined) {
            const weight = parseFloat(formData.weight);
            if (isNaN(weight) || weight < 0) {
                validationErrors.weight = 'Weight must be a valid number greater than or equal to 0.';
            }
        }
        
        return validationErrors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});
        setGeneralError('');

        // Client-side validation
        const validationErrors = validateForm();
        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            setGeneralError('Please fill in all required fields correctly.');
            setLoading(false);
            
            // Scroll to first error
            setTimeout(() => {
                const firstErrorField = Object.keys(validationErrors)[0];
                if (firstErrorField) {
                    const element = document.querySelector(`[name="${firstErrorField}"]`);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        element.focus();
                    }
                }
            }, 100);
            return;
        }

        try {
            const token = getToken();
            const formDataToSend = new FormData();
            
            Object.keys(formData).forEach(key => {
                // Always include description even if empty (required field)
                if (key === 'description') {
                    formDataToSend.append(key, formData[key] || formData['short_description'] || formData['name'] || 'No description provided');
                } else if (formData[key] !== '' && formData[key] !== null && formData[key] !== undefined) {
                    if (typeof formData[key] === 'boolean') {
                        formDataToSend.append(key, formData[key] ? '1' : '0');
                    } else {
                        formDataToSend.append(key, formData[key]);
                    }
                }
            });

            // Add colors
            if (selectedColors.length > 0) {
                selectedColors.forEach(color => {
                    formDataToSend.append('colors[]', color);
                });
            }

            // Add sizes per color
            Object.keys(colorSizes).forEach(color => {
                if (colorSizes[color] && colorSizes[color].length > 0) {
                    colorSizes[color].forEach(size => {
                        formDataToSend.append(`color_sizes[${color}][]`, size);
                    });
                }
            });

            // Add color-specific main images
            Object.keys(colorMainImage).forEach(color => {
                if (colorMainImage[color]) {
                    formDataToSend.append(`color_main_image[${color}]`, colorMainImage[color]);
                }
            });

            // Add color-specific images
            Object.keys(colorImages).forEach(color => {
                if (colorImages[color] && colorImages[color].length > 0) {
                    colorImages[color].forEach((file, index) => {
                        formDataToSend.append(`color_images[${color}][]`, file);
                    });
                }
            });

            // Add color-specific main videos
            Object.keys(colorMainVideo).forEach(color => {
                if (colorMainVideo[color]) {
                    formDataToSend.append(`color_main_video[${color}]`, colorMainVideo[color]);
                }
            });

            // Add color-specific videos
            Object.keys(colorVideos).forEach(color => {
                if (colorVideos[color] && colorVideos[color].length > 0) {
                    colorVideos[color].forEach((file, index) => {
                        formDataToSend.append(`color_videos[${color}][]`, file);
                    });
                }
            });

            // Add variation stock quantities
            Object.keys(variationStock).forEach(key => {
                formDataToSend.append(`variation_stock[${key}]`, variationStock[key] || 0);
            });

            if (formData.image) {
                formDataToSend.append('image', formData.image);
            }
            if (formData.images && formData.images.length > 0) {
                formData.images.forEach(img => formDataToSend.append('images[]', img));
            }
            if (formData.video) {
                formDataToSend.append('video', formData.video);
            }
            if (formData.videos && formData.videos.length > 0) {
                formData.videos.forEach(vid => formDataToSend.append('videos[]', vid));
            }


            const url = isEdit 
                ? '/api/admin/products/update' 
                : '/api/admin/products/store';
            
            if (isEdit) {
                formDataToSend.append('id', productId);
            }

            const res = await axios.post(url, formDataToSend, {
                headers: {
                    AdminToken: token,
                    'Content-Type': 'multipart/form-data'
                }
            });

            if (res.data && res.data.status) {
                router.visit('/admin/products');
            } else {
                const errorData = res.data?.data?.errors || {};
                setErrors(errorData);
                if (res.data?.message) {
                    setErrors({ ...errorData, _general: res.data.message });
                }
                setGeneralError(res.data?.message || 'Please fix the errors below and try again.');
                
                // Scroll to first error
                setTimeout(() => {
                    const firstErrorField = Object.keys(errorData)[0];
                    if (firstErrorField) {
                        const element = document.querySelector(`[name="${firstErrorField}"]`);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            element.focus();
                        }
                    }
                }, 100);
            }
        } catch (err) {
            if (err.response?.data?.data?.errors) {
                const errorData = err.response.data.data.errors;
                setErrors(errorData);
                setGeneralError(err.response?.data?.message || 'Validation failed. Please check the errors below.');
                
                // Scroll to first error
                setTimeout(() => {
                    const firstErrorField = Object.keys(errorData)[0];
                    if (firstErrorField) {
                        const element = document.querySelector(`[name="${firstErrorField}"]`);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            element.focus();
                        }
                    }
                }, 100);
            } else {
                setGeneralError(err.message || 'Failed to save product. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    };

    // Remove token from URL immediately - use localStorage/cookies only
    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('token')) {
                // Extract token and save to localStorage if not already there
                const token = url.searchParams.get('token');
                if (token && !localStorage.getItem('admin_token')) {
                    localStorage.setItem('admin_token', token);
                }
                // Remove token from URL immediately
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

    if (loading && isEdit) {
        return (
            <AdminLayout>
                <Head title="Loading..." />
                <div className="p-6 text-center">
                    <Text>Loading product...</Text>
                </div>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout>
            <Head title={isEdit ? 'Edit Product' : 'Create Product'} />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6">
                    <Link href={`/admin/products`} className="text-primary-600 hover:text-primary-700 mb-4 inline-block">
                        ← Back to Products
                    </Link>
                    <Heading level={1}>{isEdit ? 'Edit Product' : 'Create New Product'}</Heading>
                </div>

                <form onSubmit={handleSubmit} noValidate>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Form */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Basic Information */}
                            <Card>
                                <Heading level={2} className="mb-4">Basic Information</Heading>
                                
                                <div className="space-y-4">
                                    <FormInput
                                        type="text"
                                        name="name"
                                        value={formData.name}
                                        onChange={handleInputChange}
                                        title="Product Name *"
                                        error={errors.name}
                                    />

                                    <FormTextarea
                                        name="short_description"
                                        value={formData.short_description}
                                        onChange={handleInputChange}
                                        rows={2}
                                        title="Short Description"
                                        error={errors.short_description}
                                    />

                                    <FormTextarea
                                        name="description"
                                        value={formData.description}
                                        onChange={handleInputChange}
                                        rows={5}
                                        title="Description"
                                        error={errors.description}
                                    />
                                </div>
                            </Card>

                            {/* Pricing & Inventory */}
                            <Card>
                                <Heading level={2} className="mb-4">Pricing & Inventory</Heading>
                                
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <FormInput
                                        type="number"
                                        name="price"
                                        value={formData.price}
                                        onChange={handleInputChange}
                                        step="0.01"
                                        min="0"
                                        title="Price *"
                                        error={errors.price}
                                    />

                                    <FormInput
                                        type="number"
                                        name="sale_price"
                                        value={formData.sale_price}
                                        onChange={handleInputChange}
                                        step="0.01"
                                        min="0"
                                        title="Sale Price"
                                        error={errors.sale_price}
                                    />

                                    <div className="space-y-1">
                                        <FormInput
                                            type="number"
                                            name="stock_quantity"
                                            value={formData.stock_quantity}
                                            onChange={handleInputChange}
                                            min="0"
                                            title="Stock Quantity *"
                                            error={errors.stock_quantity}
                                        />
                                        {selectedColors.length > 0 && Object.keys(variationStock).length > 0 && (
                                            <Text className="text-xs text-gray-500">
                                                Auto-calculated from variations: {calculateTotalStock()} units
                                            </Text>
                                        )}
                                    </div>

                                    <FormInput
                                        type="number"
                                        name="weight"
                                        value={formData.weight}
                                        onChange={handleInputChange}
                                        step="0.01"
                                        min="0"
                                        title="Weight (kg)"
                                        error={errors.weight}
                                    />
                                </div>
                            </Card>

                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Category */}
                            <Card>
                                <Heading level={2} className="mb-4">Category</Heading>
                                <FormSelect
                                    name="category_id"
                                    value={formData.category_id}
                                    onChange={handleInputChange}
                                    title="Category *"
                                    error={errors.category_id}
                                >
                                    <option value="">Select Category</option>
                                    {categories.map((cat) => (
                                        <option key={cat.uuid} value={cat.id}>{cat.name}</option>
                                    ))}
                                </FormSelect>
                            </Card>

                            {/* Colors - Card-based UI - Only show for clothing categories */}
                            {formData.category_id && (() => {
                                const selectedCategory = categories.find(cat => cat.id == formData.category_id);
                                const isClothing = selectedCategory && isClothingCategory(selectedCategory.name);
                                return isClothing ? (
                                    <>
                                        {/* Colors - Card-based UI */}
                                        <Card>
                                            <div className="space-y-4">
                                                <div className="flex items-center justify-between">
                                                    <Heading level={2}>Colors & Variations</Heading>
                                                    <div className="flex-1 max-w-xs ml-4">
                                                        <input
                                                            type="text"
                                                            placeholder="Add color (press Enter)"
                                                            onKeyDown={handleColorAdd}
                                                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
                                                        />
                                                    </div>
                                                </div>
                                                
                                                {selectedColors.length === 0 ? (
                                                    <div className="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                                                        <Text className="text-gray-500">No colors added yet. Add a color to start managing variations.</Text>
                                                    </div>
                                                ) : (
                                                    <div className="space-y-4">
                                                        {selectedColors.map((color) => (
                                                            <Card key={color} className="border-2 border-primary-200">
                                                                <div className="space-y-4">
                                                                    {/* Color Header */}
                                                                    <div className="flex items-center justify-between pb-3 border-b">
                                                                        <div className="flex items-center gap-3">
                                                                            <div 
                                                                                className="w-8 h-8 rounded-full border-2 border-gray-300"
                                                                                style={{ backgroundColor: color.toLowerCase() === 'red' ? '#ef4444' : 
                                                                                                color.toLowerCase() === 'blue' ? '#3b82f6' : 
                                                                                                color.toLowerCase() === 'black' ? '#000000' : 
                                                                                                color.toLowerCase() === 'white' ? '#ffffff' : 
                                                                                                color.toLowerCase() === 'gray' || color.toLowerCase() === 'grey' ? '#6b7280' : 
                                                                                                color.toLowerCase() === 'green' ? '#10b981' : 
                                                                                                color.toLowerCase() === 'yellow' ? '#fbbf24' : 
                                                                                                color.toLowerCase() === 'orange' ? '#f97316' : 
                                                                                                color.toLowerCase() === 'purple' ? '#a855f7' : 
                                                                                                color.toLowerCase() === 'pink' ? '#ec4899' : '#9ca3af' }}
                                                                            />
                                                                            <Heading level={3} className="text-lg font-semibold text-gray-800">
                                                                                {color}
                                                                            </Heading>
                                                                        </div>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => handleColorRemove(color)}
                                                                            className="px-3 py-1.5 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md transition-colors"
                                                                        >
                                                                            Remove Color
                                                                        </button>
                                                                    </div>

                                                                    {/* Sizes Selection for this Color */}
                                                                    <div className="space-y-3">
                                                                        <Text className="text-sm font-medium text-gray-700">Available Sizes</Text>
                                                                        {availableSizes.length > 0 ? (
                                                                            <div className="flex flex-wrap gap-2">
                                                                                {availableSizes.map((size) => {
                                                                                    const colorSizesList = colorSizes[color] || [];
                                                                                    const isSelected = colorSizesList.includes(size);
                                                                                    return (
                                                                                        <button
                                                                                            key={size}
                                                                                            type="button"
                                                                                            onClick={() => handleSizeToggle(color, size)}
                                                                                            className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${
                                                                                                isSelected
                                                                                                    ? 'bg-primary-600 text-white hover:bg-primary-700'
                                                                                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                                                                            }`}
                                                                                        >
                                                                                            {size}
                                                                                        </button>
                                                                                    );
                                                                                })}
                                                                            </div>
                                                                        ) : (
                                                                            <Text className="text-sm text-gray-500">No sizes available for this category</Text>
                                                                        )}
                                                                    </div>

                                                                    {/* Stock by Size for this Color */}
                                                                    {(colorSizes[color] && colorSizes[color].length > 0) ? (
                                                                        <div className="space-y-3">
                                                                            <div className="flex items-center justify-between">
                                                                                <Text className="text-sm font-medium text-gray-700">Stock by Size</Text>
                                                                                <div className="flex items-center gap-2">
                                                                                    <input
                                                                                        type="number"
                                                                                        min="0"
                                                                                        placeholder="Default"
                                                                                        className="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-primary-500"
                                                                                        onKeyDown={(e) => {
                                                                                            if (e.key === 'Enter') {
                                                                                                const defaultStock = e.target.value;
                                                                                                handleSetDefaultStock(color, defaultStock);
                                                                                                e.target.value = '';
                                                                                            }
                                                                                        }}
                                                                                    />
                                                                                    <button
                                                                                        type="button"
                                                                                        onClick={(e) => {
                                                                                            const input = e.target.previousElementSibling;
                                                                                            if (input && input.value) {
                                                                                                handleSetDefaultStock(color, input.value);
                                                                                                input.value = '';
                                                                                            }
                                                                                        }}
                                                                                        className="px-2 py-1 text-xs bg-primary-600 text-white rounded hover:bg-primary-700 transition-colors"
                                                                                    >
                                                                                        Apply to All
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                                                                {colorSizes[color].map((size) => {
                                                                                    const key = `${size}_${color}`;
                                                                                    const stockValue = variationStock[key] || 0;
                                                                                    return (
                                                                                        <div key={size} className="space-y-1">
                                                                                            <label className="text-xs font-medium text-gray-600">
                                                                                                Size: {size}
                                                                                            </label>
                                                                                            <FormInput
                                                                                                type="number"
                                                                                                min="0"
                                                                                                value={stockValue}
                                                                                                onChange={(e) => handleStockChange(size, color, e.target.value)}
                                                                                                className="w-full"
                                                                                                placeholder="0"
                                                                                                title={`Stock for ${size} - ${color}`}
                                                                                            />
                                                                                        </div>
                                                                                    );
                                                                                })}
                                                                            </div>
                                                                            <div className="text-xs text-gray-500 bg-gray-50 p-2 rounded">
                                                                                Total stock for {color}: <span className="font-semibold">{colorSizes[color].reduce((sum, size) => {
                                                                                    const key = `${size}_${color}`;
                                                                                    return sum + (parseInt(variationStock[key]) || 0);
                                                                                }, 0)}</span> units
                                                                            </div>
                                                                        </div>
                                                                    ) : (
                                                                        <div className="text-center py-4 bg-gray-50 rounded-md">
                                                                            <Text className="text-sm text-gray-500">Select sizes above to manage stock for this color</Text>
                                                                        </div>
                                                                    )}

                                                                    {/* Main Image for this Color */}
                                                                    <div className="space-y-2">
                                                                        <Text className="text-sm font-medium text-gray-700">Main Image</Text>
                                                                        <FormInput
                                                                            type="file"
                                                                            accept="image/*"
                                                                            onChange={(e) => handleColorMainImageChange(color, e)}
                                                                            title={`Upload main image for ${color}`}
                                                                            error={errors[`color_main_image_${color}`]}
                                                                        />
                                                                        {colorMainImagePreview[color] && (
                                                                            <div className="mt-2">
                                                                                <div className="relative inline-block">
                                                                                    <img
                                                                                        src={colorMainImagePreview[color]}
                                                                                        alt={`${color} - Main Image`}
                                                                                        className="w-32 h-32 object-cover rounded border-2 border-primary-500"
                                                                                    />
                                                                                    <span className="absolute top-1 right-1 bg-primary-600 text-white text-xs px-2 py-1 rounded">
                                                                                        Main
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        )}
                                                                    </div>

                                                                    {/* Additional Images for this Color */}
                                                                    <div className="space-y-2">
                                                                        <Text className="text-sm font-medium text-gray-700">Additional Images</Text>
                                                                        <FormInput
                                                                            type="file"
                                                                            accept="image/*"
                                                                            multiple
                                                                            onChange={(e) => handleColorImageChange(color, e)}
                                                                            title={`Upload additional images for ${color}`}
                                                                            error={errors[`color_images_${color}`]}
                                                                        />
                                                                        {colorImagesPreview[color] && colorImagesPreview[color].length > 0 && (
                                                                            <div className="mt-2 grid grid-cols-4 gap-2">
                                                                                {colorImagesPreview[color].map((preview, idx) => (
                                                                                    <div key={idx} className="relative group">
                                                                                        <img
                                                                                            src={preview}
                                                                                            alt={`${color} - Preview ${idx + 1}`}
                                                                                            className="w-full h-24 object-cover rounded border border-gray-200"
                                                                                        />
                                                                                    </div>
                                                                                ))}
                                                                            </div>
                                                                        )}
                                                                    </div>

                                                                    {/* Main Video for this Color */}
                                                                    <div className="space-y-2">
                                                                        <Text className="text-sm font-medium text-gray-700">Main Video</Text>
                                                                        <FormInput
                                                                            type="file"
                                                                            accept="video/mp4,video/avi,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/webm"
                                                                            onChange={(e) => handleColorMainVideoChange(color, e)}
                                                                            title={`Upload main video for ${color}`}
                                                                            error={errors[`color_main_video_${color}`]}
                                                                        />
                                                                        {colorMainVideoPreview[color] && (
                                                                            <div className="mt-2">
                                                                                <div className="relative inline-block">
                                                                                    <video
                                                                                        src={colorMainVideoPreview[color]}
                                                                                        controls
                                                                                        className="w-64 h-36 object-cover rounded border-2 border-primary-500"
                                                                                    >
                                                                                        Your browser does not support the video tag.
                                                                                    </video>
                                                                                    <span className="absolute top-1 right-1 bg-primary-600 text-white text-xs px-2 py-1 rounded">
                                                                                        Main
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        )}
                                                                    </div>

                                                                    {/* Additional Videos for this Color */}
                                                                    <div className="space-y-2">
                                                                        <Text className="text-sm font-medium text-gray-700">Additional Videos</Text>
                                                                        <FormInput
                                                                            type="file"
                                                                            accept="video/mp4,video/avi,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/webm"
                                                                            multiple
                                                                            onChange={(e) => handleColorVideoChange(color, e)}
                                                                            title={`Upload additional videos for ${color}`}
                                                                            error={errors[`color_videos_${color}`]}
                                                                        />
                                                                        {colorVideosPreview[color] && colorVideosPreview[color].length > 0 && (
                                                                            <div className="mt-2 grid grid-cols-2 gap-2">
                                                                                {colorVideosPreview[color].map((preview, idx) => (
                                                                                    <video
                                                                                        key={idx}
                                                                                        src={preview}
                                                                                        controls
                                                                                        className="w-full h-32 object-cover rounded border border-gray-200"
                                                                                    >
                                                                                        Your browser does not support the video tag.
                                                                                    </video>
                                                                                ))}
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </Card>
                                                        ))}
                                                    </div>
                                                )}
                                                
                                                {errors.colors && (
                                                    <Text className="text-sm text-red-600">{errors.colors}</Text>
                                                )}
                                            </div>
                                        </Card>
                                    </>
                                ) : null;
                            })()}

                            {/* General Images - Only show if no colors selected (for non-clothing products) */}
                            {(() => {
                                const selectedCategory = categories.find(cat => cat.id == formData.category_id);
                                const isClothing = selectedCategory && isClothingCategory(selectedCategory.name);
                                const showGeneralMedia = !isClothing || selectedColors.length === 0;
                                
                                return showGeneralMedia ? (
                                    <>
                                        <Card>
                                            <Heading level={2} className="mb-4">Images</Heading>
                                            <div className="space-y-4">
                                                <div>
                                                    <FormInput
                                                        type="file"
                                                        accept="image/*"
                                                        onChange={handleImageChange}
                                                        title="Main Image"
                                                        error={errors.image}
                                                    />
                                                    {imagePreview && (
                                                        <img
                                                            src={imagePreview}
                                                            alt="Preview"
                                                            className="mt-2 w-full h-32 object-cover rounded"
                                                        />
                                                    )}
                                                </div>

                                                <div>
                                                    <FormInput
                                                        type="file"
                                                        accept="image/*"
                                                        multiple
                                                        onChange={handleImagesChange}
                                                        title="Additional Images"
                                                        error={errors.images}
                                                    />
                                                    {imagesPreview.length > 0 && (
                                                        <div className="mt-2 grid grid-cols-2 gap-2">
                                                            {imagesPreview.map((preview, idx) => (
                                                                <img
                                                                    key={idx}
                                                                    src={preview}
                                                                    alt={`Preview ${idx + 1}`}
                                                                    className="w-full h-24 object-cover rounded"
                                                                />
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </Card>

                                        <Card>
                                            <Heading level={2} className="mb-4">Videos</Heading>
                                            <div className="space-y-4">
                                                <div>
                                                    <FormInput
                                                        type="file"
                                                        accept="video/mp4,video/avi,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/webm"
                                                        onChange={handleVideoChange}
                                                        title="Main Video"
                                                        error={errors.video}
                                                    />
                                                    {videoPreview && (
                                                        <video
                                                            src={videoPreview}
                                                            controls
                                                            className="mt-2 w-full h-32 object-cover rounded"
                                                        >
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    )}
                                                </div>

                                                <div>
                                                    <FormInput
                                                        type="file"
                                                        accept="video/mp4,video/avi,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/webm"
                                                        multiple
                                                        onChange={handleVideosChange}
                                                        title="Additional Videos"
                                                        error={errors.videos}
                                                    />
                                                    {videosPreview.length > 0 && (
                                                        <div className="mt-2 grid grid-cols-1 gap-2">
                                                            {videosPreview.map((preview, idx) => (
                                                                <video
                                                                    key={idx}
                                                                    src={preview}
                                                                    controls
                                                                    className="w-full h-24 object-cover rounded"
                                                                >
                                                                    Your browser does not support the video tag.
                                                                </video>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </Card>
                                    </>
                                ) : (
                                    <Card>
                                        <div className="text-center py-6">
                                            <Text className="text-gray-500">
                                                Images and videos are managed within each color card above.
                                            </Text>
                                        </div>
                                    </Card>
                                );
                            })()}

                            {/* Status */}
                            <Card>
                                <Heading level={2} className="mb-4">Status</Heading>
                                <div className="space-y-3">
                                    <FormCheckbox
                                        name="is_active"
                                        checked={formData.is_active}
                                        onChange={handleInputChange}
                                        label="Active"
                                        nomargin
                                    />
                                    <FormCheckbox
                                        name="is_featured"
                                        checked={formData.is_featured}
                                        onChange={handleInputChange}
                                        label="Featured"
                                        nomargin
                                    />
                                    <FormCheckbox
                                        name="in_stock"
                                        checked={formData.in_stock}
                                        onChange={handleInputChange}
                                        label="In Stock"
                                        nomargin
                                    />
                                </div>
                            </Card>

                            {/* Actions */}
                            <Card>
                                <div className="space-y-3">
                                    <Button type="submit" block disabled={loading}>
                                        {loading ? 'Saving...' : isEdit ? 'Update Product' : 'Create Product'}
                                    </Button>
                                    <Link href={`/admin/products`}>
                                        <Button variant="outline" block>Cancel</Button>
                                    </Link>
                                </div>
                            </Card>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

