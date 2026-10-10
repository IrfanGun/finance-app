export const iconCategories = [
    'All',
    'Sport',
    'Money',
    'Food & Drink',
    'Travel',
    'Health',
    'Home',
    'Work & Learning',
    'Nature',
    'Technology',
    'Media',
    'Shopping',
    'Interface',
    'Other',
];

const categoryPatterns = [
    {
        category: 'Sport',
        pattern: /(dumbbell|weight|trophy|medal|volley|tennis|basketball|football|soccer|baseball|hockey|bike|bicycle|ski|surf|swim|running|rowing|fitness|sport|goal|podium|whistle|racquet)/,
    },
    {
        category: 'Money',
        pattern: /(bank|wallet|coin|creditcard|dollar|euro|currency|receipt|piggy|candlestick|landmark|trending|cash|calculator|chart|percent|badge)/,
    },
    {
        category: 'Food & Drink',
        pattern: /(utensil|coffee|pizza|burger|sandwich|soup|carrot|apple|banana|fish|chef|fork|knife|cup|beer|wine|milk|cake|cookie|salad|egg|popcorn|grape|cherry|citrus|avocado|food)/,
    },
    {
        category: 'Travel',
        pattern: /(plane|train|bus|car|ship|boat|map|compass|navigation|luggage|ticket|globe|hotel|route|suitcase|rocket|taxi)/,
    },
    {
        category: 'Health',
        pattern: /(heart|medical|hospital|stethoscope|pill|syringe|bandage|thermometer|wheelchair|accessibility|dna|microscope|ambulance|firstaid|tooth|lungs|health|brain)/,
    },
    {
        category: 'Home',
        pattern: /(house|home|sofa|bed|lamp|door|key|bath|couch|refrigerator|washingmachine|fence|armchair|building|chair)/,
    },
    {
        category: 'Work & Learning',
        pattern: /(briefcase|book|graduation|school|library|pencil|pen|notebook|presentation|code|terminal|lightbulb|projector|teacher|work)/,
    },
    {
        category: 'Nature',
        pattern: /(leaf|tree|flower|sun|moon|cloud|rain|snow|wind|water|mountain|bird|paw|bug|butterfly|cat|dog|earth|plant|cactus|nature|fish)/,
    },
    {
        category: 'Technology',
        pattern: /(smartphone|phone|computer|laptop|desktop|monitor|keyboard|mouse|server|database|wifi|bluetooth|router|printer|usb|chip|cpu|harddrive|tablet|battery|device)/,
    },
    {
        category: 'Media',
        pattern: /(music|audio|video|image|film|play|pause|microphone|headphones|speaker|radio|podcast|disc|gallery|volume|cassette|media)/,
    },
    {
        category: 'Shopping',
        pattern: /(shopping|cart|bag|basket|tag|store|package|gift|barcode)/,
    },
    {
        category: 'Interface',
        pattern: /(arrow|align|check|chevron|circle|corner|cursor|ellipsis|filter|grid|layout|list|menu|more|move|panel|search|settings|sort|square|toggle|view|x$)/,
    },
];

export const getIconCategory = (name) => {
    const normalizedName = name.toLowerCase().replace(/[^a-z]/g, '');
    const match = categoryPatterns.find(({ pattern }) => pattern.test(normalizedName));

    return match?.category ?? 'Other';
};
