import { computed, ref } from 'vue';
import {
    Bike, BriefcaseBusiness, Bus, Building2, Camera, CarFront, CircleDollarSign,
    Clapperboard, Coffee, Dumbbell, Footprints, Gamepad2, Gift, GraduationCap,
    HeartPulse, House, Laptop, Lightbulb, Music, PawPrint, PiggyBank, Plane,
    Shirt, ShoppingBag, ShoppingCart, Smartphone, Target, Wallet,
} from 'lucide-vue-next';

const planIcons = {
    'shopping-bag': ShoppingBag,
    target: Target,
    wallet: Wallet,
    house: House,
    'car-front': CarFront,
    plane: Plane,
    footprints: Footprints,
    'heart-pulse': HeartPulse,
    'graduation-cap': GraduationCap,
    gift: Gift,
    coffee: Coffee,
    'briefcase-business': BriefcaseBusiness,
    music: Music,
    'paw-print': PawPrint,
    'shopping-cart': ShoppingCart,
    bus: Bus,
    bike: Bike,
    'building-2': Building2,
    camera: Camera,
    'circle-dollar-sign': CircleDollarSign,
    clapperboard: Clapperboard,
    dumbbell: Dumbbell,
    'gamepad-2': Gamepad2,
    laptop: Laptop,
    lightbulb: Lightbulb,
    'piggy-bank': PiggyBank,
    shirt: Shirt,
    smartphone: Smartphone,
};

const planColorGradients = {
    blue: ['#2457DA', '#5D84E8'],
    red: ['#B34735', '#D87667'],
    green: ['#16815B', '#29996E'],
    orange: ['#B96B17', '#CC821F'],
    purple: ['#7046B8', '#8F6ACD'],
    teal: ['#087E8B', '#278E98'],
};

export function useBudgetPlanDisplay(props) {
    const planIcon = (icon) => planIcons[icon] ?? ShoppingBag;
    const planIconStyle = (color) => {
        const [start, end] = planColorGradients[color] ?? planColorGradients.blue;

        return { backgroundImage: `linear-gradient(135deg, ${start}, ${end})` };
    };
    const localDate = (date) => {
        const parts = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit',
        }).formatToParts(date);
        const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));

        return `${values.year}-${values.month}-${values.day}`;
    };
    const today = localDate(new Date());
    const formatMoney = (amount) => new Intl.NumberFormat('id-ID').format(Number(amount ?? 0));
    const displayAccountCurrency = (account) => account.currency
        ?? (account.type === 'investment' ? 'mata uang belum diatur' : 'IDR');
    const periodUsagePercentage = (period) => {
        const allocation = Number(period.allocated_amount ?? 0);
        const spent = Number(period.spent ?? 0);

        if (allocation <= 0) {
            return 0;
        }

        return Math.min(Math.max((spent / allocation) * 100, 0), 100);
    };
    const periodUsageColor = (period) => {
        const percentage = periodUsagePercentage(period);

        if (percentage >= 100) {
            return 'bg-[#B34735]';
        }

        if (percentage >= 75) {
            return 'bg-[#D99A1B]';
        }

        return 'bg-[#2457DA]';
    };
    const periodStatusLabel = (status) => ({
        awaiting_funding: 'Menunggu dana', open: 'Berjalan', funded: 'Didanai', closed: 'Selesai',
    }[status] ?? status);
    const planStatusLabel = (status) => ({
        active: 'Aktif', paused: 'Dijeda', ended: 'Dihentikan', completed: 'Selesai',
    }[status] ?? status);
    const planStatusColor = (status) => ({
        active: 'bg-budget-success-surface text-budget-success',
        paused: 'bg-[#FFF4DB] text-[#9A6700]',
        ended: 'bg-[#EEF1F5] text-[#637083]',
        completed: 'bg-budget-goal-surface text-budget-goal',
    }[status] ?? 'bg-[#EEF1F5] text-[#637083]');
    const selectedPlan = computed(() => props.plans.find(
        (plan) => Number(plan.id) === Number(props.selectedPlanId),
    ) ?? null);
    const availableCurrencies = computed(() => Object.keys(props.totals));
    const selectedCurrency = ref(
        props.totals.IDR ? 'IDR' : Object.keys(props.totals)[0] ?? 'IDR',
    );
    const secondaryCurrency = computed(() => availableCurrencies.value.find(
        (currency) => currency !== selectedCurrency.value,
    ) ?? null);
    const activePlanFilter = ref('all');
    const monthLabel = new Intl.DateTimeFormat('id-ID', {
        month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta',
    }).format(new Date());
    const activePlanCount = computed(() => props.plans.filter(
        (plan) => plan.status === 'active',
    ).length);
    const filteredPlans = computed(() => {
        if (activePlanFilter.value === 'active') {
            return props.plans.filter((plan) => plan.status === 'active');
        }

        if (activePlanFilter.value === 'completed') {
            return props.plans.filter((plan) => ['completed', 'ended'].includes(plan.status));
        }

        return props.plans;
    });
    const formatCurrency = (amount, currency = 'IDR') => {
        const safeCurrency = String(currency || 'IDR').trim();
        const value = Number(amount ?? 0);

        try {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency', currency: safeCurrency, maximumFractionDigits: 0,
            }).format(value);
        } catch {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
            }).format(value);
        }
    };
    const formatDate = (date) => {
        if (!date) {
            return '';
        }

        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta',
        }).format(new Date(`${date}T00:00:00+07:00`));
    };
    const isDetailPage = computed(() => props.selectedPlanId !== null);
    const currentPeriodForPlan = (plan) =>
        plan.periods?.find((period) => period.status === 'open')
            ?? plan.periods?.find((period) => period.status === 'awaiting_funding')
            ?? plan.periods?.[0]
            ?? null;
    const goalProgressPercentage = (plan) => {
        const target = Number(plan.target_amount ?? 0);

        return target > 0
            ? Math.min((Number(plan.goal_funded ?? 0) / target) * 100, 100)
            : 0;
    };
    const availableCategories = computed(() => props.categories.filter(
        (category) => category.type === 'expense',
    ));

    return {
        activePlanCount, activePlanFilter, availableCategories, availableCurrencies,
        currentPeriodForPlan, displayAccountCurrency, filteredPlans, formatCurrency,
        formatDate, formatMoney, goalProgressPercentage, isDetailPage, monthLabel,
        periodStatusLabel, periodUsageColor, periodUsagePercentage, planIcon,
        planIconStyle, planStatusColor, planStatusLabel, secondaryCurrency,
        selectedCurrency, selectedPlan, today,
    };
}
