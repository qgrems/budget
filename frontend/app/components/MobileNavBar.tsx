"use client"

import type React from "react"

import Link from "next/link"
import { useState } from "react"
import { LanguageSelector } from "./LanguageSelector"
import { useAppContext } from "../providers"
import { usePathname } from "next/navigation"
import { useTranslation } from "../hooks/useTranslation"

export function MobileNavBar() {
    const [isMenuOpen, setIsMenuOpen] = useState(false)
    const pathname = usePathname()
    const { t } = useTranslation()

    const toggleMenu = () => setIsMenuOpen(!isMenuOpen)
    const {
        state: { isAuthenticated: appIsAuthenticated, user: appUser, loading: appLoading },
    } = useAppContext()
    const NavLink = ({
                         href,
                         children,
                         onClick,
                     }: { href: string; children: React.ReactNode; onClick?: (e: React.MouseEvent<HTMLAnchorElement>) => void }) => (
        <Link
            href={href}
            className={`block py-2 px-0 md:px-2 ${pathname === href ? "text-primary font-bold active-link" : "text-gray-600"}`}
            onClick={(e) => {
                if (onClick) {
                    onClick(e)
                } else {
                    setIsMenuOpen(false)
                }
            }}
        >
            {children}
        </Link>
    )

    return (
        <>
            {appIsAuthenticated ? (
                <div className="mobile-nav-bar">
                    <NavLink href={"/envelopes"}>Enveloppes</NavLink>
                    <NavLink href={"/budget-tracker"}>Budget</NavLink>
                    <NavLink href={"/settings"}>Settings</NavLink>
                    <LanguageSelector />
                </div>
            ) : (
                <div className="mobile-nav-bar">
                    <NavLink href={"/"}>Home</NavLink>
                    <NavLink href="/signin">{t("header.signIn")}</NavLink>
                    <NavLink href="/signup">{t("header.signUp")}</NavLink>
                    <LanguageSelector />
                </div>
            )}
        </>
    )
}
