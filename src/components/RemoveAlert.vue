<template>
  <div>
    <dialog id="modal_remove" class="modal" :open="showModal">
      <div class="modal-box">
        <h3 class="font-bold text-lg">{{ modalTitle }}</h3>
        <button
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          @click="closeModal"
        >
          ✕
        </button>
        <div class="text-center text-red-400 mb-2">
          <i class="fas fa-exclamation-triangle text-4xl"></i>
        </div>
        <h2 class="text-lg font-semibold text-center">{{ title }}</h2>
        <p class="my-4 text-center text-xl">{{ message }}</p>
        <div class="flex justify-between space-x-4">
          <button @click="onCancel" class="btn btn-secondary rounded-full">
            {{ cancelText }}
          </button>
          <button
            @click="onConfirm"
            class="btn rounded-full btn-error text-white"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </dialog>
  </div>
</template>
  
  <script>
export default {
  props: {
    isVisible: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: "Confirmation",
    },
    message: {
      type: String,
      default: "Are you sure?",
    },
    confirmText: {
      type: String,
      default: "Confirm",
    },
    cancelText: {
      type: String,
      default: "Cancel",
    },
  },
  methods: {
    onConfirm() {
      this.$emit("confirm");
      this.closeModal();
    },
    onCancel() {
      this.$emit("cancel");
      this.closeModal();
    },
    closeModal() {
      const modal = document.getElementById("modal_remove");
      modal.close();
    },
  },
};
</script>