import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { store, update, destroy } from '@/actions/App/Http/Controllers/CategoryController';

const defaults = {
    name: '',
    type: 'expense',
    icon: 'heart',
    color: '#2457DA',
    is_active: true,
};

export function useCategoryForm() {
    const open = ref(false);
    const form = useForm({ ...defaults });

    const close = () => {
        form.reset();
        form.clearErrors();
        form.id = null;
        open.value = false;
    };

    const create = () => {
        form.reset();
        form.clearErrors();
        form.id = null;
        open.value = true;
    };

    const edit = (category) => {
        Object.assign(form, {
            id: category.id,
            name: category.name,
            type: category.type,
            icon: category.icon,
            color: category.color,
            is_active: category.is_active,
        });
        open.value = true;
    };

    const save = () => {
        const action = form.id ? form.put : form.post;
        const route = form.id ? update.url(form.id) : store.url();

        action.call(form, route, { onSuccess: close });
    };

    const remove = (category) => {
        if (confirm(`Delete ${category.name}?`)) {
            form.delete(destroy.url(category.id));
        }
    };

    const toggleActive = (category) => {
        useForm({
            name: category.name,
            type: category.type,
            icon: category.icon,
            color: category.color,
            is_active: !category.is_active,
        }).put(update.url(category.id), { preserveScroll: true });
    };

    return { open, form, create, edit, save, remove, toggleActive, close };
}
