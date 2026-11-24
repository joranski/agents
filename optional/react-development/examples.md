# React Development — Inertia v2 & Modern Patterns

## 1. Replacing Filament Resources with Inertia.js v2 & React

Instead of heavy Filament panels, render clean React pages via Laravel Inertia v2:

```tsx
// resources/js/Pages/Admin/Users/Index.tsx
import { Head, useForm, router } from '@inertiajs/react';
import { ReactCrudTable, UserItem } from '@/Components/ReactCrudTable';
import { Result } from '@/architecture/Result';

interface Props {
  users: UserItem[];
}

export default function UsersIndex({ users }: Props) {
  const handleSave = async (data: any, id?: number): Promise<Result<UserItem, string>> => {
    return new Promise((resolve) => {
      if (id) {
        router.put(`/admin/users/${id}`, data, {
          onSuccess: (page) => resolve(Result.success(data as UserItem)),
          onError: (errors) => resolve(Result.failure(Object.values(errors)[0] as string)),
        });
      } else {
        router.post('/admin/users', data, {
          onSuccess: (page) => resolve(Result.success(data as UserItem)),
          onError: (errors) => resolve(Result.failure(Object.values(errors)[0] as string)),
        });
      }
    });
  };

  const handleDelete = async (id: number): Promise<Result<void, string>> => {
    return new Promise((resolve) => {
      router.delete(`/admin/users/${id}`, {
        onSuccess: () => resolve(Result.success(undefined)),
        onError: (errors) => resolve(Result.failure("Delete failed")),
      });
    });
  };

  return (
    <>
      <Head title="User Administration" />
      <ReactCrudTable initialUsers={users} onSave={handleSave} onDelete={handleDelete} />
    </>
  );
}
```

---

## 2. Declarative Form Handling with Inertia v2 `useForm`

```tsx
import { useForm } from '@inertiajs/react';

export function EditProfileForm({ user }: { user: { name: string; email: string } }) {
  const { data, setData, post, processing, errors } = useForm({
    name: user.name,
    email: user.email,
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/profile');
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      <div>
        <label className="text-sm font-medium">Name</label>
        <input
          value={data.name}
          onChange={(e) => setData('name', e.target.value)}
          className="w-full rounded border px-3 py-2"
        />
        {errors.name && <p className="text-xs text-red-500 mt-1">{errors.name}</p>}
      </div>
      <button type="submit" disabled={processing} className="btn-primary">
        Save Changes
      </button>
    </form>
  );
}
```

---

## 3. Surgical Vitest Testing Pattern

```bash
# Surgical: single component with fast bail
npx vitest run resources/js/Components/ReactCrudTable.test.tsx --bail=1
```
