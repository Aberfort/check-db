import React from 'react'
import { NavLink } from 'react-router-dom'
import Container from './Container.jsx'

function NavItem({ to, children }) {
    return (
        <NavLink
            to={to}
            className={({ isActive }) =>
                [
                    'rounded-lg px-3 py-2 text-sm font-semibold border transition',
                    isActive
                        ? 'bg-brand-50 text-brand border-brand-200/60'
                        : 'bg-white text-ink/70 border-brand-200/60 hover:bg-brand-50',
                ].join(' ')
            }
        >
            {children}
        </NavLink>
    )
}

export default function AppHeader({ right = null }) {
    return (
        <header className="border-b border-brand-200/70 bg-brand-50/60 backdrop-blur">
            <Container className="py-5 flex items-center justify-between gap-4">
                <div className="min-w-0">
                    <div className="text-sm text-brand-800/70">Database health checker</div>
                    <div className="flex items-center gap-3">
                        <h1 className="text-2xl font-extrabold tracking-tight text-ink">
                            check-db <span className="text-brand">dashboard</span>
                        </h1>

                        <div className="hidden md:flex items-center gap-2">
                            <NavItem to="/">Overview</NavItem>
                            <NavItem to="/databases">Databases</NavItem>
                            <NavItem to="/settings">Settings</NavItem>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <div className="md:hidden flex items-center gap-2">
                        <NavItem to="/">Overview</NavItem>
                        <NavItem to="/databases">DBs</NavItem>
                        <NavItem to="/settings">⚙</NavItem>
                    </div>

                    {right}
                </div>
            </Container>
        </header>
    )
}
