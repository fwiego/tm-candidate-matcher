export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `btn-pill-outline ${disabled ? 'opacity-50 cursor-not-allowed' : ''} ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}