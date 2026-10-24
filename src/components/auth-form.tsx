'use client';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { useState } from 'react';
import { api } from '@/lib/client';
import { Button } from './ui/button';
export function AuthForm({ register = false }: { register?: boolean }) {
  const router = useRouter(),
    search = useSearchParams();
  const [error, setError] = useState(''),
    [busy, setBusy] = useState(false);
  async function submit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setBusy(true);
    setError('');
    const data = Object.fromEntries(new FormData(e.currentTarget));
    try {
      const r = await api<{ role: string }>(`auth/${register ? 'register' : 'login'}`, data);
      const requested = search.get('next');
      const parsed = requested?.startsWith('/') ? new URL(requested, window.location.origin) : null;
      const target =
        parsed?.origin === window.location.origin
          ? parsed.pathname + parsed.search + parsed.hash
          : null;
      router.push(
        target?.startsWith('/') && !target.startsWith('//')
          ? target
          : r.role === 'ADMIN'
            ? '/admin'
            : '/tai-khoan',
      );
      router.refresh();
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  }
  return (
    <div className="auth-wrap">
      <span className="eyebrow">WELCOME TO KH FASHION</span>
      <h1 style={{ marginTop: 15 }}>{register ? 'Tạo tài khoản' : 'Chào mừng trở lại'}</h1>
      <p className="muted">
        {register
          ? 'Một tài khoản cho trải nghiệm mua sắm của riêng bạn.'
          : 'Đăng nhập để theo dõi đơn hàng và mua sắm dễ dàng.'}
      </p>
      <div className="auth-tabs">
        <Link
          href={`/dang-nhap${search.get('next') ? '?next=' + encodeURIComponent(search.get('next')!) : ''}`}
          className={!register ? 'selected' : ''}
        >
          Đăng nhập
        </Link>
        <Link
          href={`/dang-ky${search.get('next') ? '?next=' + encodeURIComponent(search.get('next')!) : ''}`}
          className={register ? 'selected' : ''}
        >
          Đăng ký
        </Link>
      </div>
      <form onSubmit={submit} className="form-stack">
        {register && (
          <label>
            Họ và tên
            <input name="name" required minLength={2} maxLength={100} autoComplete="name" />
          </label>
        )}
        <label>
          Email
          <input name="email" type="email" required autoComplete="email" maxLength={200} />
        </label>
        <label>
          Mật khẩu
          <input
            name="password"
            type="password"
            required
            minLength={8}
            maxLength={72}
            autoComplete={register ? 'new-password' : 'current-password'}
          />
        </label>
        {register && (
          <p className="muted">Dùng ít nhất 8 ký tự. Không dùng mật khẩu từ tài khoản khác.</p>
        )}
        {error && (
          <div className="alert" role="alert">
            {error}
          </div>
        )}
        <Button disabled={busy} type="submit">
          {busy ? 'Đang xử lý...' : register ? 'Tạo tài khoản' : 'Đăng nhập'}
        </Button>
      </form>
    </div>
  );
}
