const components = {
    HelloProvider: () =>
        import(
            /* webpackChunkName: "hello-provider" */ './HelloProvider.vue'
        ),
};

export default (Vue) => {
    for (const name in components) {
        Vue.component(name, components[name])
    }
};