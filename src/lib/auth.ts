import { getIronSession } from 'iron-session';
import { cookies } from 'next/headers';
import { db } from './db';
import { AppError } from './errors';
import { redirect } from 'next/navigation';
export async function session() {
  const password = process.env.SESSION_SECRET;
  if (!password || password.length < 32)
    throw new Error('SESSION_SECRET must contain at least 32 characters');
  return getIronSession<{ userId?: string }>(await cookies(), {
    password,
    cookieName: 'kh-session',
    ttl: 60 * 60 * 24 * 7,
    cookieOptions: {
      httpOnly: true,
      secure: process.env.COOKIE_SECURE === 'true',
      sameSite: 'lax',
      path: '/',
    },
  });
}
export async function currentUser() {
  const s = await session();
  return s.userId
    ? db.user.findUnique({
        where: { id: s.userId },
        select: { id: true, name: true, email: true, role: true },
      })
    : null;
}
export async function requireUser(admin = false) {
  const user = await currentUser();
  if (!user) throw new AppError('Vui lòng đăng nhập để tiếp tục', 401);
  if (admin && user.role !== 'ADMIN')
    throw new AppError('Bạn không có quyền thực hiện thao tác này', 403);
  return user;
}
export async function pageUser(admin = false) {
  const user = await currentUser();
  if (!user) redirect('/dang-nhap');
  if (admin && user.role !== 'ADMIN') redirect('/tai-khoan');
  return user;
}
