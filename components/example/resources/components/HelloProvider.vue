<script>
export default {
    name: 'HelloProvider',

    methods: {
        clickHandler() {
            window.alert('hello world');
        }
    },

    render () {
        return this.$scopedSlots.default({
            $clickHandler: this.clickHandler
        });
    }
};
</script>
