(() => {
    const form = document.querySelector('.blog-admin-editor form');
    const editorNode = document.getElementById('article-body-visual');
    if (!form || !editorNode || !window.Quill || !window.QuillTableBetter) return;

    const source = document.getElementById('article-body-source');
    const plain = document.getElementById('article-body-plain');
    const initial = document.getElementById('article-body-initial').value;
    const initialVisual = document.getElementById('article-body-initial-visual').value;
    const startMode = document.getElementById('article-body-start-mode').value;
    const upload = document.getElementById('article-image-upload');
    const value = document.getElementById('article-body-value');
    let mode = startMode;
    let lastVisualHtml = initialVisual;

    Quill.register({'modules/table-better': QuillTableBetter}, true);
    const modules = {
        table: false,
        toolbar: {
            container: '#blog-quill-toolbar',
            handlers: {
                link: addLink,
                image: () => upload.click()
            }
        },
        'table-better': {
            language: 'en_US',
            menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
            toolbarTable: true
        },
        keyboard: {bindings: QuillTableBetter.keyboardBindings}
    };
    if (window.QuillResizeImage) {
        Quill.register('modules/resize', window.QuillResizeImage);
        modules.resize = {keepAspectRatio: true, persistAlignment: true, resizeConstraints: {minWidth: 30, minHeight: 30, maxWidth: 1600, maxHeight: 1600}, disableMediaTypes: {disableVideos: true, disableIframes: true}};
    }
    const quill = new Quill(editorNode, {
        theme: 'snow',
        placeholder: 'Start writing your article…',
        modules
    });

    const setVisualHtml = html => {
        quill.setText('');
        const delta = quill.clipboard.convert({html});
        const [range] = quill.selection.getRange();
        quill.updateContents(delta, Quill.sources.USER);
        quill.setSelection(delta.length() - (range?.length || 0), Quill.sources.SILENT);
        quill.scrollSelectionIntoView();
    };
    setVisualHtml(initialVisual);
    plain.value = initial;
    source.value = initial;

    function addLink(enabled) {
        if (!enabled) return quill.format('link', false);
        const url = window.prompt('Enter the link URL');
        if (url && /^(https?:|mailto:|tel:|\/)/i.test(url.trim()) && !url.trim().startsWith('//')) {
            quill.format('link', url.trim());
        }
    }

    const htmlToPlain = html => {
        const box = document.createElement('div');
        box.innerHTML = html;
        return box.innerText || box.textContent || '';
    };
    const plainToHtml = text => text.trim().split(/\n\s*\n+/).filter(Boolean)
        .map(paragraph => '<p>' + paragraph.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>') + '</p>').join('');
    const visualHtml = () => {
        quill.getModule('table-better').deleteTableTemporary();
        return quill.getSemanticHTML();
    };
    const currentHtml = () => mode === 'visual' ? visualHtml() : mode === 'plain' ? plainToHtml(plain.value) : source.value;
    const switchMode = next => {
        if (mode === 'visual') lastVisualHtml = visualHtml();
        const html = mode === 'visual' ? lastVisualHtml : currentHtml();
        if (next === 'visual' && mode !== 'visual') setVisualHtml(html);
        if (next === 'plain' && mode !== 'plain') plain.value = htmlToPlain(html);
        if (next === 'html' && mode !== 'html') source.value = html;
        mode = next;
        editorNode.hidden = mode !== 'visual';
        document.getElementById('blog-quill-toolbar').hidden = mode !== 'visual';
        plain.hidden = mode !== 'plain';
        source.hidden = mode !== 'html';
        document.querySelectorAll('.blog-mode-button').forEach(button => {
            const active = button.dataset.mode === mode;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
        });
    };
    document.querySelectorAll('.blog-mode-button').forEach(button => button.addEventListener('click', () => switchMode(button.dataset.mode)));
    switchMode(mode);

    upload.addEventListener('change', async () => {
        const file = upload.files[0];
        if (!file) return;
        const body = new FormData();
        body.append('image', file);
        try {
            const response = await fetch('admin/image-upload.php', {
                method: 'POST',
                headers: {'X-CSRF-Token': form.querySelector('[name="csrf_token"]').value},
                body
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Image upload failed.');
            const range = quill.getSelection(true);
            quill.insertEmbed(range.index, 'image', result.url, Quill.sources.USER);
            quill.setSelection(range.index + 1, Quill.sources.SILENT);
        } catch (error) {
            window.alert(error.message);
        } finally {
            upload.value = '';
        }
    });

    form.addEventListener('submit', event => {
        if (mode === 'visual') lastVisualHtml = visualHtml();
        value.value = mode === 'visual' ? lastVisualHtml : mode === 'plain' ? plain.value : source.value;
        if (!value.value.trim()) {
            event.preventDefault();
            window.alert('Article body is required.');
        }
    });
})();
