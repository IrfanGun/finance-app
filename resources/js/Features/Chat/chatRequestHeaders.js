export const chatRequestHeaders = () => {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    };
    const xsrfCookie = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    if (xsrfCookie) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie.split('=')[1]);
    }

    return headers;
};
