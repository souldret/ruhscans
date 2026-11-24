#!/usr/bin/env bash
# Ruhscans canlı güvenlik duman kontrol betiği (savunma amaçlı yapılandırma doğrulaması).
# Kullanım: bash scripts/security-smoke.sh https://ruhscans.com [kilitli-seri-slug] [kilitli-bolum-no]

set -u

BASE_URL="${1:-https://ruhscans.com}"
BASE_URL="${BASE_URL%/}"
SERIES_SLUG="${2:-}"
CHAPTER_NO="${3:-}"

PASS=0
FAIL=0

check_status() {
    local label="$1"
    local method="$2"
    local path="$3"
    local expected_regex="$4"
    local extra_args="${5:-}"

    local code
    code=$(curl -sS -o /dev/null -w "%{http_code}" -X "$method" $extra_args "${BASE_URL}${path}")

    if [[ "$code" =~ ^($expected_regex)$ ]]; then
        echo "[PASS] ${label} -> HTTP ${code} (beklenen: ${expected_regex})"
        PASS=$((PASS + 1))
    else
        echo "[FAIL] ${label} -> HTTP ${code} (beklenen: ${expected_regex})"
        FAIL=$((FAIL + 1))
    fi
}

check_header() {
    local label="$1"
    local header_name="$2"
    local headers="$3"

    if echo "$headers" | grep -qi "^${header_name}:"; then
        echo "[PASS] Başlık mevcut: ${label} (${header_name})"
        PASS=$((PASS + 1))
    else
        echo "[FAIL] Başlık eksik: ${label} (${header_name})"
        FAIL=$((FAIL + 1))
    fi
}

echo "=== Ruhscans Güvenlik Duman Kontrolü: ${BASE_URL} ==="
echo

# 1. Hassas dosya ve dizin sızıntı kontrolleri
check_status "Hassas .env erişimi kapalı" GET "/.env" "403|404"
check_status "Git yapılandırması kapalı" GET "/.git/config" "403|404"
check_status "Laravel log dosyası kapalı" GET "/storage/logs/laravel.log" "403|404"
check_status "Vendor autoload kapalı" GET "/vendor/autoload.php" "403|404"

# 2. Yetkisiz yönetim/izleme uç noktaları
check_status "Horizon yetkisiz kapalı" GET "/horizon" "302|401|403|404"
check_status "Telescope yok/kapalı" GET "/telescope" "403|404"
check_status "Harici /api yok/kapalı" GET "/api" "403|404"
check_status "Admin panel yetkisiz yönlendirme/engel" GET "/admin" "302|401|403"
check_status "Kod CSV indirme yetkisiz kapalı" GET "/admin/kodlar/indir?token=test" "302|401|403|404"

# 3. Kilitli bölüm yetkisiz kontrolü (opsiyonel parametre verilirse)
if [[ -n "$SERIES_SLUG" && -n "$CHAPTER_NO" ]]; then
    body=$(curl -sS -H "Accept: application/json" -b "ruhscans_age_confirmed=1" "${BASE_URL}/seri/${SERIES_SLUG}/bolum/${CHAPTER_NO}/sayfalar")
    if echo "$body" | grep -q '"locked":true' && ! echo "$body" | grep -q '"pages"'; then
        echo "[PASS] Kilitli bölüm sayfa listesi görsel URL sızdırmıyor"
        PASS=$((PASS + 1))
    else
        echo "[FAIL] Kilitli bölüm yanıtı beklenmedik: ${body:0:140}"
        FAIL=$((FAIL + 1))
    fi
else
    echo "[SKIP] Kilitli bölüm kontrolü için seri slug ve bölüm no verin: bash scripts/security-smoke.sh ${BASE_URL} <slug> <bolum>"
fi

# 4. Kod kullanma yetkisiz + CSRF koruması
check_status "Kod kullanma yetkisiz/CSRF reddi" POST "/cuzdan/kod" "302|401|403|419|429"

# 5. Güvenlik başlıkları
HEADERS=$(curl -sSI "${BASE_URL}/")
check_header "CSP" "Content-Security-Policy" "$HEADERS"
check_header "HSTS" "Strict-Transport-Security" "$HEADERS"
check_header "Clickjacking koruması" "X-Frame-Options" "$HEADERS"
check_header "MIME sniffing koruması" "X-Content-Type-Options" "$HEADERS"
check_header "Referrer politikası" "Referrer-Policy" "$HEADERS"
check_header "İzin politikası" "Permissions-Policy" "$HEADERS"
check_header "COOP" "Cross-Origin-Opener-Policy" "$HEADERS"

echo
echo "=== Sonuç: ${PASS} PASS, ${FAIL} FAIL ==="
if [[ "$FAIL" -gt 0 ]]; then
    exit 1
fi
