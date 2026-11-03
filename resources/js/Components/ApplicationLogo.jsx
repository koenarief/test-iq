export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <img
            {...props}
            src="/logo-hr.png"
            alt="Logo"
            className={`${className} object-contain`}
        />
    );
}
