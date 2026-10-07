<?php

declare(strict_types=1);

/**
 * Renderiza blocos ```mermaid do Markdown como diagramas. A biblioteca só é baixada
 * quando a página tem algum diagrama; sem JS, o código-fonte do diagrama continua legível.
 */
?>
<script type="module">
    const blocks = document.querySelectorAll('pre > code.language-mermaid');
    if (blocks.length) {
        const { default: mermaid } = await import('https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs');
        blocks.forEach((code) => {
            const figure = document.createElement('div');
            figure.className = 'mermaid';
            figure.textContent = code.textContent;
            code.parentElement.replaceWith(figure);
        });
        mermaid.initialize({ startOnLoad: false, securityLevel: 'strict', theme: 'neutral', fontFamily: 'Inter, system-ui, sans-serif' });
        await mermaid.run({ querySelector: '.mermaid' });
    }
</script>
