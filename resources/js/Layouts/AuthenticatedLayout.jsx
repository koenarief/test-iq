import Dropdown from '@/Components/Dropdown';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const NAV_ITEMS = [
    { href: 'dashboard', pattern: 'dashboard', label: 'Dashboard' },
    {
        href: 'admin.ist-questions.index',
        pattern: 'admin.ist-questions.*',
        label: 'Soal IST',
    },
    {
        href: 'admin.ist-answer-keys.index',
        pattern: 'admin.ist-answer-keys.*',
        label: 'Kunci Jawaban',
    },
    {
        href: 'admin.ist-results.index',
        pattern: 'admin.ist-results.*',
        label: 'Hasil IST',
    },
    {
        href: 'admin.disc-results.index',
        pattern: 'admin.disc-results.*',
        label: 'Hasil DISC',
    },
    {
        href: 'admin.competency-results.index',
        pattern: 'admin.competency-results.*',
        label: 'Hasil Kompetensi',
    },
    { href: 'admin.users.index', pattern: 'admin.users.*', label: 'User' },
    {
        href: 'admin.merchants.index',
        pattern: 'admin.merchants.*',
        label: 'Merchant',
    },
];

function PillNavLink({ href, active, children }) {
    return (
        <Link
            href={href}
            className={
                'rounded-full px-3 py-1.5 text-[13px] font-medium transition ' +
                (active
                    ? 'bg-zinc-900 text-white'
                    : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800')
            }
        >
            {children}
        </Link>
    );
}

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="font-admin min-h-screen bg-[#F7F8FA] text-zinc-900 antialiased">
            <nav className="sticky top-0 z-10 border-b border-zinc-200/80 bg-white/90 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center">
                            <Link
                                href="/"
                                className="flex shrink-0 items-center gap-2.5"
                            >
                                <span className="grid h-9 w-9 place-items-center rounded-xl bg-zinc-900 text-[14px] font-bold text-white">
                                    A
                                </span>
                                <span className="hidden text-[14px] font-extrabold tracking-[-0.02em] text-zinc-900 sm:block">
                                    Admin Panel
                                </span>
                            </Link>

                            <div className="hidden items-center gap-1 sm:ms-8 sm:flex">
                                {NAV_ITEMS.map((item) => (
                                    <PillNavLink
                                        key={item.href}
                                        href={route(item.href)}
                                        active={route().current(
                                            item.pattern,
                                        )}
                                    >
                                        {item.label}
                                    </PillNavLink>
                                ))}
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-full">
                                            <button
                                                type="button"
                                                className="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-[13px] font-semibold text-zinc-700 shadow-sm transition hover:text-zinc-900"
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-full p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 focus:bg-zinc-100 focus:text-zinc-600 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' border-t border-zinc-200/80 sm:hidden'
                    }
                >
                    <div className="space-y-1 px-2 pb-3 pt-2">
                        {NAV_ITEMS.map((item) => (
                            <ResponsiveNavLink
                                key={item.href}
                                href={route(item.href)}
                                active={route().current(item.pattern)}
                            >
                                {item.label}
                            </ResponsiveNavLink>
                        ))}
                    </div>

                    <div className="border-t border-zinc-200/80 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-zinc-800">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-zinc-500">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-b border-zinc-200/80 bg-white/60">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
