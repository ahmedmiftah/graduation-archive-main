const STATUS_COLORS: Record<string, string> = {
    available: 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400',
    reserved: 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400',
    completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
    closed: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
};

const STATUS_LABELS: Record<string, string> = {
    available: 'متاحة',
    reserved: 'محجوزة',
    completed: 'مكتملة',
    closed: 'مغلقة',
};

export function ideaStatusColor(status: string): string {
    return STATUS_COLORS[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
}

export function ideaStatusLabel(status: string): string {
    return STATUS_LABELS[status] ?? status;
}
