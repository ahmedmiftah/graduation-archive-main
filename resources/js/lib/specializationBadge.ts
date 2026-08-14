// Deterministic badge color for specialization names, so the same
// specialization always gets the same color across pages/sessions.
const PALETTE = [
    'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    'bg-purple-100 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400',
    'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
    'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
    'bg-pink-100 text-pink-700 dark:bg-pink-900/20 dark:text-pink-400',
    'bg-teal-100 text-teal-700 dark:bg-teal-900/20 dark:text-teal-400',
    'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400',
    'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400',
];

export function specializationBadgeColor(name?: string | null): string {
    if (!name) return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    }
    return PALETTE[hash % PALETTE.length];
}
