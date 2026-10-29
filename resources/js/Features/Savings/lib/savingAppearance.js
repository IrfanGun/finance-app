import {
    Heart,
    House,
    Landmark,
    PiggyBank,
    Plane,
    ShieldCheck,
    ShoppingBag,
    Wallet,
} from 'lucide-vue-next';

export const savingIconOptions = [
    { value: 'piggy-bank', label: 'Celengan', component: PiggyBank },
    { value: 'shield-check', label: 'Perisai', component: ShieldCheck },
    { value: 'landmark', label: 'Gedung', component: Landmark },
    { value: 'wallet', label: 'Dompet', component: Wallet },
    { value: 'shopping-bag', label: 'Belanja', component: ShoppingBag },
    { value: 'plane', label: 'Perjalanan', component: Plane },
    { value: 'house', label: 'Rumah', component: House },
    { value: 'heart', label: 'Kesehatan', component: Heart },
];

export const savingColorOptions = [
    {
        value: 'blue',
        label: 'Biru',
        gradient: 'linear-gradient(135deg, #2457DA, #5D84E8)',
    },
    {
        value: 'violet',
        label: 'Ungu',
        gradient: 'linear-gradient(135deg, #7047D6, #A17AEA)',
    },
    {
        value: 'green',
        label: 'Hijau',
        gradient: 'linear-gradient(135deg, #07845B, #42B98A)',
    },
    {
        value: 'orange',
        label: 'Oranye',
        gradient: 'linear-gradient(135deg, #D76619, #F3A34C)',
    },
    {
        value: 'rose',
        label: 'Merah muda',
        gradient: 'linear-gradient(135deg, #D83E67, #F17C94)',
    },
    {
        value: 'teal',
        label: 'Toska',
        gradient: 'linear-gradient(135deg, #078C98, #4BC5C2)',
    },
];

export function getSavingIcon(iconValue) {
    return savingIconOptions.find((option) => option.value === iconValue)?.component
        ?? savingIconOptions[0].component;
}

export function getSavingGradient(colorValue) {
    return savingColorOptions.find((option) => option.value === colorValue)?.gradient
        ?? savingColorOptions[0].gradient;
}
