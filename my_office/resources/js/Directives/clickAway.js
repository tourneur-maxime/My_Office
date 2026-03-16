export default {
    mounted(el, binding) {
        el.clickAwayEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event);
            }
        };
        document.addEventListener('click', el.clickAwayEvent);
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickAwayEvent);
    },
};
