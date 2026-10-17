export const weekdays = [
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
    'Minggu',
];

export const createEmptyMealForm = () => ({
    name: '',
    place_name: '',
    type: 'buy',
    price_min: '',
    price_max: '',
    rating: null,
    address: '',
    impression: '',
    opening_hours: [],
});

export const formatMoney = (amount) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
}).format(Number(amount ?? 0));

export const formatMealPrice = (meal) => {
    if (Number(meal.price_min) === Number(meal.price_max)) {
        return `Rp ${formatMoney(meal.price_min)}`;
    }

    return `Rp ${formatMoney(meal.price_min)} – ${formatMoney(meal.price_max)}`;
};

export const getOpeningHoursForForm = (openingHours = []) => openingHours.map(
    (session) => ({
        day_of_week: session.day_of_week,
        opens_at: session.opens_at.slice(0, 5),
        closes_at: session.closes_at.slice(0, 5),
    }),
);

export const getScheduleMode = (openingHours) => {
    const sessionsByDay = weekdays.map((day, index) => openingHours
        .filter((session) => session.day_of_week === index + 1)
        .map(({ opens_at, closes_at }) => ({
            opens_at,
            closes_at,
        })));
    const firstDaySchedule = JSON.stringify(sessionsByDay[0]);
    const hasSameScheduleEveryDay = sessionsByDay.every(
        (sessions) => JSON.stringify(sessions) === firstDaySchedule,
    );

    return {
        mode: hasSameScheduleEveryDay ? 'everyday' : 'custom',
        dailySessions: hasSameScheduleEveryDay ? sessionsByDay[0] : [],
    };
};

export const prepareMealFormData = (data, {
    hasOpeningHours,
    scheduleMode,
    dailySessions,
}) => {
    const openingHours = data.type !== 'buy' || !hasOpeningHours
        ? []
        : scheduleMode === 'everyday'
            ? weekdays.flatMap((day, index) => dailySessions.map((session) => ({
                day_of_week: index + 1,
                opens_at: session.opens_at,
                closes_at: session.closes_at,
                closes_next_day: session.closes_at < session.opens_at,
            })))
            : data.opening_hours.map((session) => ({
                ...session,
                closes_next_day: session.closes_at < session.opens_at,
            }));

    return {
        ...data,
        place_name: data.type === 'buy' ? data.place_name : null,
        address: data.type === 'buy' ? data.address : null,
        impression: data.type === 'buy' ? data.impression : null,
        opening_hours: openingHours,
    };
};

export const getMealStatusLabel = (status) => ({
    open: 'Buka',
    closed: 'Tutup',
    unknown: 'Jam buka belum diketahui',
})[status] ?? 'Jam buka belum diketahui';
