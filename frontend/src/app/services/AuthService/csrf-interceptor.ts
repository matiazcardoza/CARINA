import { HttpInterceptorFn } from '@angular/common/http';

export const csrfInterceptor: HttpInterceptorFn = (req, next) => {
  const token = getCookieValue('XSRF-TOKEN');

  let headers: { [key: string]: string } = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };

  if (!(req.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
  }

  if (token) {
    headers['X-XSRF-TOKEN'] = decodeURIComponent(token);
  }

  const modifiedReq = req.clone({
    setHeaders: headers
  });

  return next(modifiedReq);
};

function getCookieValue(name: string): string | null {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop()?.split(';').shift() || null;
  }
  return null;
}