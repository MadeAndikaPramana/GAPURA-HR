import Sidebar from '@/Layouts/Sidebar';
import TopNav from '@/Layouts/TopNav';

export default function AuthenticatedLayout({ header, children }) {
    return (
        <div className="flex h-screen bg-gray-50">
            <Sidebar />
            <div className="flex flex-1 flex-col">
                <TopNav />
                <main className="flex-1 p-6">
                    {header && (
                        <header className="bg-white shadow">
                            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                                {header}
                            </div>
                        </header>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}