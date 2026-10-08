(function () {
    'use strict';

    // Confirmação antes de enviar formulários destrutivos: <form data-confirm="Tem certeza?">
    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-confirm]');
        if (form && !window.confirm(form.getAttribute('data-confirm'))) {
            event.preventDefault();
        }
    });

    // Some com as mensagens flash depois de alguns segundos.
    document.querySelectorAll('.flash').forEach(function (el) {
        setTimeout(function () {
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });

    // Prévia do slug a partir do título/nome.
    function slugify(text) {
        return text.normalize('NFD').replace(/[̀-ͯ]/g, '')
            .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
    var slugSource = document.querySelector('[data-slug-source]');
    var slugTarget = document.querySelector('[data-slug-target]');
    var slugPreview = document.querySelector('[data-slug-preview]');
    function updateSlugPreview() {
        if (!slugPreview) return;
        var value = (slugTarget && slugTarget.value) || (slugSource && slugify(slugSource.value)) || '…';
        slugPreview.textContent = value;
    }
    if (slugSource) slugSource.addEventListener('input', updateSlugPreview);
    if (slugTarget) slugTarget.addEventListener('input', updateSlugPreview);
    updateSlugPreview();

    // Contador de caracteres para campos com maxlength.
    document.querySelectorAll('textarea[data-counter][maxlength]').forEach(function (el) {
        var label = el.closest('.field') && el.closest('.field').querySelector('label');
        if (!label) return;
        var counter = document.createElement('span');
        counter.className = 'counter';
        label.appendChild(counter);
        var update = function () { counter.textContent = el.value.length + '/' + el.maxLength; };
        el.addEventListener('input', update);
        update();
    });

    // Barra de ferramentas Markdown.
    document.querySelectorAll('[data-md-toolbar]').forEach(function (toolbar) {
        var textarea = document.getElementById(toolbar.getAttribute('data-md-toolbar'));
        if (!textarea) return;

        var wrap = function (before, after, placeholder) {
            var start = textarea.selectionStart, end = textarea.selectionEnd;
            var selected = textarea.value.slice(start, end) || placeholder;
            textarea.setRangeText(before + selected + after, start, end, 'end');
            textarea.setSelectionRange(start + before.length, start + before.length + selected.length);
            textarea.focus();
        };
        var prefixLines = function (prefix, placeholder) {
            var start = textarea.value.lastIndexOf('\n', textarea.selectionStart - 1) + 1;
            var end = textarea.selectionEnd;
            var block = textarea.value.slice(start, end) || placeholder;
            var replaced = block.split('\n').map(function (line) { return prefix + line; }).join('\n');
            textarea.setRangeText(replaced, start, end, 'end');
            textarea.focus();
        };

        var actions = {
            heading: function () { prefixLines('## ', 'Título da seção'); },
            bold: function () { wrap('**', '**', 'texto em negrito'); },
            italic: function () { wrap('_', '_', 'texto em itálico'); },
            link: function () { wrap('[', '](https://)', 'texto do link'); },
            quote: function () { prefixLines('> ', 'citação'); },
            code: function () { wrap('\n```\n', '\n```\n', 'código'); },
            list: function () { prefixLines('- ', 'item'); }
        };

        toolbar.addEventListener('click', function (event) {
            var button = event.target.closest('button[data-md]');
            if (button && actions[button.getAttribute('data-md')]) {
                actions[button.getAttribute('data-md')]();
            }
        });

        // Tab insere indentação em vez de sair do campo.
        textarea.addEventListener('keydown', function (event) {
            if (event.key === 'Tab' && !event.shiftKey) {
                event.preventDefault();
                textarea.setRangeText('    ', textarea.selectionStart, textarea.selectionEnd, 'end');
            }
        });
    });
})();

// Capa do post: prévia imediata do arquivo escolhido + arrastar e soltar.
(function () {
    'use strict';
    document.querySelectorAll('[data-cover]').forEach(function (field) {
        var drop = field.querySelector('.cover-drop');
        var input = field.querySelector('input[type=file]');
        var img = field.querySelector('.cover-drop__img');
        var remove = field.querySelector('.cover-remove input');
        if (!drop || !input || !img) return;

        function preview(file) {
            if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type)) return;
            img.src = URL.createObjectURL(file);
            img.hidden = false;
            drop.classList.add('has-image');
            if (remove) remove.checked = false;
        }

        input.addEventListener('change', function () { preview(input.files[0]); });

        ['dragenter', 'dragover'].forEach(function (type) {
            drop.addEventListener(type, function (event) { event.preventDefault(); drop.classList.add('is-dragging'); });
        });
        ['dragleave', 'drop'].forEach(function (type) {
            drop.addEventListener(type, function (event) { event.preventDefault(); drop.classList.remove('is-dragging'); });
        });
        drop.addEventListener('drop', function (event) {
            var files = event.dataTransfer && event.dataTransfer.files;
            if (!files || !files.length) return;
            input.files = files;
            preview(files[0]);
        });

        if (remove) {
            remove.addEventListener('change', function () { drop.classList.toggle('is-removed', remove.checked); });
        }
    });
})();
