<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="window.directZipUpload({
            statePath: @js($getStatePath()),
            chapterId: {{ $getChapterId() }},
            presignUrl: @js(route('admin.zip.direct.presign')),
            multipartUrl: @js(route('admin.zip.direct.multipart.start')),
            completeMultipartUrl: @js(route('admin.zip.direct.multipart.complete')),
            completeUrl: @js(route('admin.zip.direct.complete')),
            csrf: @js(csrf_token()),
        })"
        class="space-y-2"
    >
        <input type="file" x-ref="file" accept=".zip,application/zip" class="hidden" @change="onFile($event)">

        <button
            type="button"
            x-on:click="$refs.file.click()"
            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-inset ring-gray-950/10 dark:ring-white/20 w-full"
        >
            <span x-text="busy ? 'Yükleniyor…' : 'Büyük ZIP seç (100 MB üstü)'"></span>
        </button>

        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10" x-show="progress > 0">
            <div class="h-full bg-primary-500 transition-all" :style="`width: ${progress}%`"></div>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="message"></p>
    </div>
</x-dynamic-component>
