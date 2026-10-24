import { Suspense } from 'react';
import { AuthForm } from '@/components/auth-form';
export const metadata = { title: 'Đăng nhập' };
export default function Page() {
  return (
    <Suspense fallback={<div className="skeleton" />}>
      <AuthForm />
    </Suspense>
  );
}
