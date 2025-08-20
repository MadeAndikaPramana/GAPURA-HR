import ApplicationLogo from '@/Components/ApplicationLogo';
import NavLink from '@/Components/NavLink';
import { Link } from '@inertiajs/react';

export default function Sidebar() {
    return (
        <aside className="w-64 flex-shrink-0 bg-sidebar-bg text-white">
            <div className="flex h-16 items-center justify-center bg-primary-green">
                <Link href="/">
                    <ApplicationLogo className="block h-9 w-auto" />
                </Link>
            </div>
            <nav className="mt-5">
                <NavLink href={route('dashboard')} active={route().current('dashboard')} className="text-white">
                    Dashboard
                </NavLink>
                <NavLink href={route('employees.index')} active={route().current('employees.index')} className="text-white">
                    Employees
                </NavLink>
                <NavLink href={route('training-records.index')} active={route().current('training-records.index')} className="text-white">
                    Training Records
                </NavLink>
                <NavLink href={route('training-types.index')} active={route().current('training-types.index')} className="text-white">
                    Training Types
                </NavLink>
                <NavLink href={route('departments.index')} active={route().current('departments.index')} className="text-white">
                    Departments
                </NavLink>
            </nav>
        </aside>
    );
}
