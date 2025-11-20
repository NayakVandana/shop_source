import axios from 'axios';
import sessionService from './utils/sessionService';

// Setup axios globally
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Initialize session service (sets up interceptors and syncs session_id)
sessionService.initialize();
