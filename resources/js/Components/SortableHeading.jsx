export default function SortableHeading({ label, column, sort, direction, onSort, className = '' }) {
    const isActive = sort === column;
    const indicator = isActive ? (direction === 'asc' ? '↑' : '↓') : '↕';

    return (
        <th scope="col" aria-sort={isActive ? (direction === 'asc' ? 'ascending' : 'descending') : 'none'} className={className}>
            <button
                type="button"
                onClick={() => onSort(column)}
                className="inline-flex items-center gap-2 text-left hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                {label}
                <span aria-hidden="true" className="text-sm normal-case">{indicator}</span>
            </button>
        </th>
    );
}
