import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>,
) {
    return (
        <img
            src="/images/magnova-logo-transparent.png"
            alt="Magnova"
            {...props}
            className={`object-contain ${props.className ?? ''}`}
        />
    );
}
