'use client';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useState } from 'react';
import { api } from '@/lib/client';
import { Button } from './ui/button';
export function AccountNav() {
  const path = usePathname(),
    router = useRouter();
  return (
    <nav className="side-nav" aria-label="Tài khoản">
      {[
        ['/tai-khoan', 'Thông tin cá nhân'],
        ['/tai-khoan/dia-chi', 'Sổ địa chỉ'],
        ['/tai-khoan/don-hang', 'Đơn hàng của tôi'],
      ].map(([href, title]) => (
        <Link key={href} href={href} className={path === href ? 'selected' : ''}>
          {title}
        </Link>
      ))}
      <button
        onClick={async () => {
          await api('auth/logout');
          router.push('/');
          router.refresh();
        }}
      >
        Đăng xuất
      </button>
    </nav>
  );
}
export function ProfileForm({ name, email }: { name: string; email: string }) {
  const [message, setMessage] = useState(''),
    [busy, setBusy] = useState(false);
  return (
    <form
      className="form-stack"
      onSubmit={async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.currentTarget));
        setBusy(true);
        try {
          await api('account/profile', data, 'PATCH');
          setMessage('Đã lưu thông tin');
        } catch (e) {
          setMessage((e as Error).message);
        } finally {
          setBusy(false);
        }
      }}
    >
      <label>
        Họ và tên
        <input name="name" defaultValue={name} required minLength={2} maxLength={100} />
      </label>
      <label>
        Email
        <input value={email} disabled />
      </label>
      {message && <div role="status">{message}</div>}
      <Button disabled={busy} style={{ justifySelf: 'start' }}>
        {busy ? 'Đang lưu...' : 'Lưu thông tin'}
      </Button>
    </form>
  );
}
export function AddressForm({
  address,
}: {
  address?: { id: string; name: string; phone: string; detail: string };
}) {
  const router = useRouter();
  const [error, setError] = useState(''),
    [busy, setBusy] = useState(false);
  return (
    <form
      className="form-stack"
      onSubmit={async (e) => {
        e.preventDefault();
        const form = e.currentTarget;
        setBusy(true);
        try {
          await api(
            address ? `account/addresses/${address.id}` : 'account/addresses',
            Object.fromEntries(new FormData(form)),
            address ? 'PATCH' : 'POST',
          );
          if (!address) form.reset();
          setError('');
          router.refresh();
        } catch (e) {
          setError((e as Error).message);
        } finally {
          setBusy(false);
        }
      }}
    >
      <div className="two-cols">
        <label>
          Tên người nhận
          <input name="name" required minLength={2} maxLength={100} defaultValue={address?.name} />
        </label>
        <label>
          Số điện thoại
          <input name="phone" type="tel" required defaultValue={address?.phone} />
        </label>
      </div>
      <label>
        Địa chỉ đầy đủ
        <input
          name="detail"
          required
          minLength={10}
          maxLength={500}
          defaultValue={address?.detail}
        />
      </label>
      {error && (
        <div className="alert" role="alert">
          {error}
        </div>
      )}
      <Button disabled={busy} style={{ justifySelf: 'start' }}>
        {address ? 'Lưu địa chỉ' : 'Thêm địa chỉ'}
      </Button>
    </form>
  );
}
export function ActionButton({
  path,
  label,
  method = 'POST',
  data = {},
  confirmMessage,
}: {
  path: string;
  label: string;
  method?: string;
  data?: unknown;
  confirmMessage?: string;
}) {
  const router = useRouter();
  const [error, setError] = useState(''),
    [busy, setBusy] = useState(false);
  return (
    <span>
      <Button
        className="small"
        variant="outline"
        disabled={busy}
        onClick={async () => {
          if (confirmMessage && !confirm(confirmMessage)) return;
          setBusy(true);
          try {
            await api(path, data, method);
            setError('');
            router.refresh();
          } catch (e) {
            setError((e as Error).message);
          } finally {
            setBusy(false);
          }
        }}
      >
        {busy ? 'Đang xử lý...' : label}
      </Button>
      {error && (
        <span className="alert" style={{ display: 'block' }} role="alert">
          {error}
        </span>
      )}
    </span>
  );
}
