import { Link } from '@inertiajs/react';

function MusyaLogo() {
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: '0px' }}>
            <svg
                width="70"
                height="50"
                viewBox="0 -3 80 85"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <circle cx="40" cy="40" r="40" stroke="#2C2A24" strokeWidth="3" fill="none" />
                <text
                    x="50%"
                    y="54"
                    textAnchor="middle"
                    fontFamily="Georgia, serif"
                    fontSize="48"
                    fontWeight="400"
                    fill="#284166"
                    fontStyle="italic"
                >
                    M
                </text>
            </svg>
            <div style={{ lineHeight: 1.2 }}>
                <div style={{ fontSize: '25px', fontWeight: '500', color: '#2C2A24', letterSpacing: '0.01em' }}>
                    <span style={{ color: '#2C2A24' }}>Musya</span>
                    <span style={{ color: '#284166' }}>Matcher</span>
                </div>
                <div style={{ fontSize: '19px', letterSpacing: '0.16em', textTransform: 'uppercase', color: '#2C2A24', fontWeight: '500' }}>
                    Candidate Platform
                </div>
            </div>
        </div>
    );
}

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <MusyaLogo />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {children}
            </div>
        </div>
    );
}