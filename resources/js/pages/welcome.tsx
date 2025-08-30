import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import AryventoryLogo from '@/assets/aryventory-logo.svg';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </header>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <div className="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                            <h1 className="mb-1 text-2xl font-bold lg:text-3xl">Welcome to Aryventory</h1>
                            <p className="mb-6 text-[#706f6c] dark:text-[#A1A09A] text-base lg:text-lg">
                                Your comprehensive sales management and monitoring platform.
                                <br />
                                Track leads, manage meetings, and drive sales success.
                            </p>
                            <ul className="mb-6 flex flex-col gap-4 lg:mb-8">
                                <li className="relative flex items-center gap-4 py-2 before:absolute before:top-1/2 before:bottom-0 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]">
                                    <span className="relative bg-white py-1 dark:bg-[#161615]">
                                        <span className="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                            <span className="h-1.5 w-1.5 rounded-full bg-[#f53003] dark:bg-[#FF4433]" />
                                        </span>
                                    </span>
                                    <span className="text-sm lg:text-base">
                                        Track and manage your sales leads efficiently
                                    </span>
                                </li>
                                <li className="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]">
                                    <span className="relative bg-white py-1 dark:bg-[#161615]">
                                        <span className="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                            <span className="h-1.5 w-1.5 rounded-full bg-[#f53003] dark:bg-[#FF4433]" />
                                        </span>
                                    </span>
                                    <span className="text-sm lg:text-base">
                                        Monitor performance with real-time analytics
                                    </span>
                                </li>
                                <li className="relative flex items-center gap-4 py-2">
                                    <span className="relative bg-white py-1 dark:bg-[#161615]">
                                        <span className="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                            <span className="h-1.5 w-1.5 rounded-full bg-[#f53003] dark:bg-[#FF4433]" />
                                        </span>
                                    </span>
                                    <span className="text-sm lg:text-base">
                                        Schedule meetings and follow-ups seamlessly
                                    </span>
                                </li>
                            </ul>
                            <ul className="flex gap-3 text-sm leading-normal">
                                <li>
                                    {auth.user ? (
                                        <Link
                                            href={route('dashboard')}
                                            className="inline-block rounded-sm border border-[#f53003] bg-[#f53003] px-6 py-2 text-sm font-medium leading-normal text-white hover:border-[#d42a02] hover:bg-[#d42a02] dark:border-[#FF4433] dark:bg-[#FF4433] dark:hover:border-[#e03929] dark:hover:bg-[#e03929]"
                                        >
                                            Go to Dashboard
                                        </Link>
                                    ) : (
                                        <Link
                                            href={route('login')}
                                            className="inline-block rounded-sm border border-[#f53003] bg-[#f53003] px-6 py-2 text-sm font-medium leading-normal text-white hover:border-[#d42a02] hover:bg-[#d42a02] dark:border-[#FF4433] dark:bg-[#FF4433] dark:hover:border-[#e03929] dark:hover:bg-[#e03929]"
                                        >
                                            Get Started
                                        </Link>
                                    )}
                                </li>
                            </ul>
                        </div>
                        <div className="relative -mb-px aspect-[335/376] w-full shrink-0 overflow-hidden rounded-t-lg bg-[#fff2f2] lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-[fff2f2] ">
                            <img className='w-full h-full object-contain' src="/assets/aryventory-logo.svg" alt="Aryventory Logo" />
                        </div>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
