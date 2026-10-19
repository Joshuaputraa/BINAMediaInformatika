import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";

export function useCrud(basePath, defaults) {
    const show = ref(false);
    const editId = ref(null);
    const form = useForm({ ...defaults });

    const openAdd = () => {
        editId.value = null;
        form.reset();
        form.clearErrors();
        show.value = true;
    };

    const openEdit = (row) => {
        editId.value = row.id;
        form.clearErrors();
        Object.keys(defaults).forEach((k) => (form[k] = row[k] ?? defaults[k]));
        show.value = true;
    };

    const close = () => {
        show.value = false;
        form.reset();
        form.clearErrors();
    };

    const submit = () => {
        const opts = { preserveScroll: true, onSuccess: close };
        editId.value
            ? form.put(`${basePath}/${editId.value}`, opts)
            : form.post(basePath, opts);
    };

    const deleting = ref(null);
    const deleteError = ref("");
    const busy = ref(false);

    const askDelete = (row) => {
        deleteError.value = "";
        deleting.value = row;
    };
    const cancelDelete = () => (deleting.value = null);
    const confirmDelete = () =>
        router.delete(`${basePath}/${deleting.value.id}`, {
            preserveScroll: true,
            onStart: () => (busy.value = true),
            onFinish: () => (busy.value = false),
            onSuccess: () => (deleting.value = null),
            onError: (e) =>
                (deleteError.value = e.delete ?? "Gagal menghapus data."),
        });

    return {
        show,
        editId,
        form,
        openAdd,
        openEdit,
        close,
        submit,
        deleting,
        deleteError,
        busy,
        askDelete,
        cancelDelete,
        confirmDelete,
    };
}
