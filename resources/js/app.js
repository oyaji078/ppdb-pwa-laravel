import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { createIcons } from 'lucide';

import { icons, normaliseIconNames } from './icons';
import './charts';
import './pwa';

Alpine.plugin(collapse);
window.Alpine = Alpine;

/**
 * Renders every <i data-lucide="name"> on the page. Re-run after Alpine swaps
 * markup so icons inside newly shown blocks are drawn too.
 */
const renderIcons = () => {
    normaliseIconNames();
    createIcons({ icons, attrs: { 'aria-hidden': 'true' } });
};

document.addEventListener('DOMContentLoaded', renderIcons);
document.addEventListener('alpine:initialized', renderIcons);
document.addEventListener('icons:refresh', renderIcons);

Alpine.start();
