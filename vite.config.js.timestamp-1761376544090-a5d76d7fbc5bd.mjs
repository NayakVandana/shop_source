// vite.config.js
import { defineConfig } from "file:///C:/wamp64/www/shop_source/node_modules/vite/dist/node/index.js";
import laravel from "file:///C:/wamp64/www/shop_source/node_modules/laravel-vite-plugin/dist/index.mjs";
import react from "file:///C:/wamp64/www/shop_source/node_modules/@vitejs/plugin-react/dist/index.js";
var vite_config_default = defineConfig({
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.jsx"],
      refresh: true
    }),
    react({
      include: "**/*.{jsx,tsx}"
    })
  ],
  server: {
    host: "127.0.0.1",
    port: 5173
  },
  esbuild: {
    jsxInject: `import React from 'react'`
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFx3YW1wNjRcXFxcd3d3XFxcXHNob3Bfc291cmNlXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ZpbGVuYW1lID0gXCJDOlxcXFx3YW1wNjRcXFxcd3d3XFxcXHNob3Bfc291cmNlXFxcXHZpdGUuY29uZmlnLmpzXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ltcG9ydF9tZXRhX3VybCA9IFwiZmlsZTovLy9DOi93YW1wNjQvd3d3L3Nob3Bfc291cmNlL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCByZWFjdCBmcm9tICdAdml0ZWpzL3BsdWdpbi1yZWFjdCc7XG5cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XG4gICAgcGx1Z2luczogW1xuICAgICAgICBsYXJhdmVsKHtcbiAgICAgICAgICAgIGlucHV0OiBbJ3Jlc291cmNlcy9jc3MvYXBwLmNzcycsICdyZXNvdXJjZXMvanMvYXBwLmpzeCddLFxuICAgICAgICAgICAgcmVmcmVzaDogdHJ1ZSxcbiAgICAgICAgfSksXG4gICAgICAgIHJlYWN0KHtcbiAgICAgICAgICAgIGluY2x1ZGU6IFwiKiovKi57anN4LHRzeH1cIixcbiAgICAgICAgfSksXG4gICAgXSxcbiAgICBzZXJ2ZXI6IHtcbiAgICAgICAgaG9zdDogJzEyNy4wLjAuMScsXG4gICAgICAgIHBvcnQ6IDUxNzMsXG4gICAgfSxcbiAgICBlc2J1aWxkOiB7XG4gICAgICAgIGpzeEluamVjdDogYGltcG9ydCBSZWFjdCBmcm9tICdyZWFjdCdgLFxuICAgIH0sXG59KTtcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFBcVEsU0FBUyxvQkFBb0I7QUFDbFMsT0FBTyxhQUFhO0FBQ3BCLE9BQU8sV0FBVztBQUVsQixJQUFPLHNCQUFRLGFBQWE7QUFBQSxFQUN4QixTQUFTO0FBQUEsSUFDTCxRQUFRO0FBQUEsTUFDSixPQUFPLENBQUMseUJBQXlCLHNCQUFzQjtBQUFBLE1BQ3ZELFNBQVM7QUFBQSxJQUNiLENBQUM7QUFBQSxJQUNELE1BQU07QUFBQSxNQUNGLFNBQVM7QUFBQSxJQUNiLENBQUM7QUFBQSxFQUNMO0FBQUEsRUFDQSxRQUFRO0FBQUEsSUFDSixNQUFNO0FBQUEsSUFDTixNQUFNO0FBQUEsRUFDVjtBQUFBLEVBQ0EsU0FBUztBQUFBLElBQ0wsV0FBVztBQUFBLEVBQ2Y7QUFDSixDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=
