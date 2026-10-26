document.addEventListener('alpine:init', () => {
    window.FilePond?.setOptions({
        chunkUploads: false,
        chunkSize: 64 * 1024 * 1024,
    });
});
