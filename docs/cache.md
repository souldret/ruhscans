# Cloudflare önbellek kuralları

Caching → Cache Rules. Sırayla.

1. Derlenmiş dosyalar
   - URI Path starts with `/build/`
   - Cache eligibility: Eligible
   - Edge TTL: 1 yıl
   - Browser TTL: 1 yıl
   - Dosya adları içerik hash’i taşır, değişince adres değişir.

2. Marka görselleri
   - URI Path starts with `/brand/`
   - Edge TTL: 1 gün
   - Browser TTL: 1 saat

3. Kapak türevleri
   - URI Path contains `-240.webp` veya `-480.webp` veya `.avif`
   - Edge TTL: 1 ay
   - Orijinal kapak (`/storage/games/covers/` ve boyut eki yok) önbelleğe alınmasın. Kuyruk türevleri üretince adres değişir.

4. HTML
   - URI Path equals `/` veya starts with `/oyunlar` veya `/oyun/`
   - Cache eligibility: Bypass
   - Sayfada yaş kapısı, çerez ve oturum var. Önbellek veritabanı katmanında, 10 dakika.

5. API ve indirme
   - URI Path starts with `/api/` veya `/indir/` veya `/hesap` veya `/yonetim`
   - Bypass

6. Sıkıştırma
   - Speed → Optimization → Brotli açık.
   - Polish ve WebP dönüşümünü kapat. Türevleri kuyruk üretiyor; çift dönüşüm orijinali tekrar büyütür.
