function directZipUpload(options) {
    return {
        busy: false,
        progress: 0,
        message: 'Dosya Cloudflare’dan geçmeden doğrudan R2’ye yüklenir.',
        file: null,

        async onFile(event) {
            const file = event.target.files?.[0];

            if (!file) {
                return;
            }

            this.file = file;
            this.busy = true;
            this.progress = 0;

            try {
                const multipart = file.size > 200 * 1024 * 1024;
                const upload = multipart
                    ? await this.uploadMultipart(file)
                    : await this.uploadSingle(file);

                await this.post(this.options.completeUrl, {
                    chapter_id: this.options.chapterId,
                    path: upload.path,
                    token: upload.token,
                });

                this.$wire.set(this.options.statePath, upload.path, true);
                this.message = 'Yükleme tamamlandı, bölüm kuyruğa alındı.';
            } catch (error) {
                this.message = error instanceof Error ? error.message : 'Yükleme başarısız.';
            } finally {
                this.busy = false;
                this.$refs.file.value = '';
            }
        },

        async uploadSingle(file) {
            const result = await this.post(this.options.presignUrl, {
                size: file.size,
                filename: file.name,
            });

            await this.put(result.url, file);

            return { path: result.path, token: result.token };
        },

        async uploadMultipart(file) {
            const started = await this.post(this.options.multipartUrl, {
                size: file.size,
                filename: file.name,
            });

            const parts = [];

            for (const part of started.parts) {
                const start = (part.part_number - 1) * started.part_size;
                const chunk = file.slice(start, Math.min(start + started.part_size, file.size));
                const response = await fetch(part.url, {
                    method: 'PUT',
                    body: chunk,
                    headers: { 'Content-Type': 'application/zip' },
                });

                if (!response.ok) {
                    throw new Error(`Parça ${part.part_number} yüklenemedi (${response.status}).`);
                }

                const etag = response.headers.get('ETag');

                if (!etag) {
                    throw new Error(`Parça ${part.part_number} için ETag alınamadı. R2 CORS ExposeHeaders ayarını kontrol edin.`);
                }

                parts.push({ part_number: part.part_number, etag: etag.replace(/^"|"$/g, '') });
                this.progress = Math.round((part.part_number / started.parts.length) * 100);
            }

            await this.post(this.options.completeMultipartUrl, {
                path: started.path,
                upload_id: started.upload_id,
                parts,
            });

            return { path: started.path, token: started.token };
        },

        async post(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                const first = Object.values(payload.errors ?? {})[0]?.[0];

                throw new Error(first ?? payload.message ?? `İstek başarısız (${response.status}).`);
            }

            return response.json();
        },

        async put(url, file) {
            const response = await fetch(url, {
                method: 'PUT',
                body: file,
                headers: { 'Content-Type': 'application/zip' },
            });

            if (!response.ok) {
                throw new Error(`R2 yüklemesi başarısız (${response.status}).`);
            }

            this.progress = 100;
        },
    };
}

window.directZipUpload = directZipUpload;
