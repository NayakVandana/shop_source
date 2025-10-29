// @ts-nocheck
import React from 'react';

export default function Footer() {
    return (
        <footer className="bg-gray-900 text-white">
            <div className="max-w-7xl mx-auto py-6 sm:py-8 px-4 sm:px-6 lg:px-8">
                <div className="text-center">
                    <p className="text-sm sm:text-base">
                        &copy; {new Date().getFullYear()} ShopSource. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    );
}

