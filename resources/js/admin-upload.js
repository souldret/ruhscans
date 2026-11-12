/**
 * Filament her FileUpload alanında kendi FilePond örneğini kurar ve
 * fileValidateTypeDetectType ile uzantıdan MIME üretir. Burada
 * FilePond.setOptions çağırmak o dedektörü siler: WEBP, AVIF, MP4 ve MOV
 * tarayıcıda "geçersiz tür" diye reddedilir, Livewire'a hiç gitmez.
 * Parça boyutu ve tür doğrulaması alan tanımında kalır.
 */
