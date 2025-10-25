// React Refresh Runtime Setup
// This file ensures React Refresh works properly with Vite and Laravel

declare global {
    interface Window {
        $RefreshReg$: (type: any, id: string) => void;
        $RefreshSig$: () => (type: any) => any;
        __vite_plugin_react_preamble_installed__: boolean;
    }
}

// Initialize React Refresh globals if not already set
if (typeof window !== 'undefined') {
    if (typeof window.$RefreshReg$ === 'undefined') {
        window.$RefreshReg$ = () => {};
    }
    if (typeof window.$RefreshSig$ === 'undefined') {
        window.$RefreshSig$ = () => (type) => type;
    }
    window.__vite_plugin_react_preamble_installed__ = true;
}

// Export for module usage
export {};
