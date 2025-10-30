// @ts-nocheck
import React from 'react';
import BaseLayout from './BaseLayout';

export default function UserLayout({ children, is404 = false }) {
    return <BaseLayout is404={is404}>{children}</BaseLayout>;
}

