<hello-provider v-slot:default="HelloProvider">
    <button
        type="button"
        @click="HelloProvider.$clickHandler"
    >
        Hello?
    </button>
</hello-provider>