import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    scenarios: {
        browse: {
            executor: 'constant-vus',
            vus: 200,
            duration: '1m',
            exec: 'browse',
        },
        download: {
            executor: 'constant-vus',
            vus: 20,
            duration: '1m',
            exec: 'download',
            startTime: '5s',
        },
    },
    thresholds: {
        'http_req_duration{name:catalog}': ['p(95)<500'],
        http_req_failed: ['rate<0.01'],
    },
};

const base = __ENV.BASE_URL || 'http://127.0.0.1:8000';

export function browse() {
    const home = http.get(`${base}/`, { tags: { name: 'catalog' } });
    check(home, { 'ana sayfa 200': (response) => response.status === 200 });

    const list = http.get(`${base}/oyunlar`, { tags: { name: 'catalog' } });
    check(list, { 'katalog 200': (response) => response.status === 200 });

    sleep(1);
}

export function download() {
    const page = http.get(`${base}/oyunlar`, { tags: { name: 'catalog' } });
    const match = page.body.match(/href="(\/oyun\/[^"]+)"/);

    if (match) {
        http.get(`${base}${match[1]}`, { tags: { name: 'detail' } });
    }

    http.post(`${base}/hesap/kod-kullan`, { code: 'YOK-YOK' }, {
        tags: { name: 'redeem' },
        redirects: 0,
    });

    sleep(2);
}
