export function BrandLogo({
  className = "h-12 object-left",
}: {
  className?: string;
}) {
  return (
    <img src="/logo.png" alt="Tidimail" className={`w-auto object-contain ${className}`} />
  );
}
