import React from 'react'

export default function SidebarLayout({ sidebar, content }) {
    return (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <aside className="lg:col-span-3 space-y-6">
                {sidebar}
            </aside>

            <section className="lg:col-span-9 space-y-6">
                {content}
            </section>
        </div>
    )
}
