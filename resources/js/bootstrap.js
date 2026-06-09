import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.apiFetch = (url, options = {}) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const headers = {
        Accept: 'application/json',
        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        ...(options.headers || {}),
    };

    return fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });
};
