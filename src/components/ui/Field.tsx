import type {
  InputHTMLAttributes,
  TextareaHTMLAttributes,
  SelectHTMLAttributes,
  ReactNode,
} from "react";
import { cn } from "@/lib/utils";

const controlBase =
  "w-full rounded-xl border border-border bg-white px-4 py-3 text-base text-text placeholder:text-text-muted focus:border-brand outline-none transition-colors";

interface FieldWrapperProps {
  label?: string;
  htmlFor?: string;
  error?: string;
  required?: boolean;
  children: ReactNode;
  className?: string;
}

export function FieldWrapper({ label, htmlFor, error, required, children, className }: FieldWrapperProps) {
  return (
    <div className={cn("text-left", className)}>
      {label ? (
        <label htmlFor={htmlFor} className="mb-1.5 block text-sm font-semibold text-text">
          {label}
          {required ? <span className="text-accent"> *</span> : null}
        </label>
      ) : null}
      {children}
      {error ? (
        <p role="alert" className="mt-1.5 text-sm font-medium text-red-700">
          {error}
        </p>
      ) : null}
    </div>
  );
}

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
}

export function Input({ label, error, id, required, className, ...props }: InputProps) {
  return (
    <FieldWrapper label={label} htmlFor={id} error={error} required={required}>
      <input
        id={id}
        required={required}
        aria-invalid={!!error}
        className={cn(controlBase, error && "border-red-400", className)}
        {...props}
      />
    </FieldWrapper>
  );
}

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
  error?: string;
}

export function Textarea({ label, error, id, required, className, ...props }: TextareaProps) {
  return (
    <FieldWrapper label={label} htmlFor={id} error={error} required={required}>
      <textarea
        id={id}
        required={required}
        aria-invalid={!!error}
        className={cn(controlBase, "min-h-32 resize-y", error && "border-red-400", className)}
        {...props}
      />
    </FieldWrapper>
  );
}

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  error?: string;
}

export function Select({ label, error, id, required, className, children, ...props }: SelectProps) {
  return (
    <FieldWrapper label={label} htmlFor={id} error={error} required={required}>
      <select
        id={id}
        required={required}
        aria-invalid={!!error}
        className={cn(controlBase, "bg-white", error && "border-red-400", className)}
        {...props}
      >
        {children}
      </select>
    </FieldWrapper>
  );
}

interface CheckboxProps extends InputHTMLAttributes<HTMLInputElement> {
  label: ReactNode;
  error?: string;
}

export function Checkbox({ label, error, id, className, ...props }: CheckboxProps) {
  return (
    <div>
      <label htmlFor={id} className={cn("flex cursor-pointer items-start gap-3 text-sm text-text-muted", className)}>
        <input
          id={id}
          type="checkbox"
          aria-invalid={!!error}
          className="mt-0.5 h-5 w-5 shrink-0 rounded border-border text-brand focus:ring-brand"
          {...props}
        />
        <span>{label}</span>
      </label>
      {error ? (
        <p role="alert" className="mt-1.5 text-sm font-medium text-red-700">
          {error}
        </p>
      ) : null}
    </div>
  );
}
