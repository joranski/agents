import React, { useState, useMemo, useTransition } from "react";
import { Result } from "../harden-logic/assets/Result"; // or @/architecture/Result

export interface UserItem {
  id: number;
  name: string;
  email: string;
  role: "admin" | "member" | "viewer";
  createdAt: string;
}

interface UserFormData {
  name: string;
  email: string;
  role: "admin" | "member" | "viewer";
}

interface UserCrudProps {
  initialUsers?: UserItem[];
  onSave?: (data: UserFormData, id?: number) => Promise<Result<UserItem, string>>;
  onDelete?: (id: number) => Promise<Result<void, string>>;
}

/**
 * Canonical React + TypeScript replacement for legacy admin/Filament resources.
 * Features: Searchable, sortable, paginated table + accessible create/edit modal with Result error handling.
 */
export function ReactCrudTable({
  initialUsers = [
    { id: 1, name: "Alice Johnson", email: "alice@example.com", role: "admin", createdAt: "2026-01-15" },
    { id: 2, name: "Bob Smith", email: "bob@example.com", role: "member", createdAt: "2026-02-10" },
    { id: 3, name: "Carol White", email: "carol@example.com", role: "viewer", createdAt: "2026-03-05" },
  ],
  onSave,
  onDelete,
}: UserCrudProps) {
  const [users, setUsers] = useState<UserItem[]>(initialUsers);
  const [search, setSearch] = useState("");
  const [sortField, setSortField] = useState<keyof UserItem>("name");
  const [sortAsc, setSortAsc] = useState(true);
  const [page, setPage] = useState(1);
  const pageSize = 5;

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<UserItem | null>(null);
  const [formData, setFormData] = useState<UserFormData>({ name: "", email: "", role: "member" });
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isPending, startTransition] = useTransition();

  // Filter & Sort
  const filteredUsers = useMemo(() => {
    return users
      .filter(
        (u) =>
          u.name.toLowerCase().includes(search.toLowerCase()) ||
          u.email.toLowerCase().includes(search.toLowerCase())
      )
      .sort((a, b) => {
        const valA = a[sortField];
        const valB = b[sortField];
        if (valA < valB) return sortAsc ? -1 : 1;
        if (valA > valB) return sortAsc ? 1 : -1;
        return 0;
      });
  }, [users, search, sortField, sortAsc]);

  const paginatedUsers = useMemo(() => {
    const start = (page - 1) * pageSize;
    return filteredUsers.slice(start, start + pageSize);
  }, [filteredUsers, page]);

  const totalPages = Math.max(1, Math.ceil(filteredUsers.length / pageSize));

  const handleOpenCreate = () => {
    setEditingUser(null);
    setFormData({ name: "", email: "", role: "member" });
    setErrorMessage(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (user: UserItem) => {
    setEditingUser(user);
    setFormData({ name: user.name, email: user.email, role: user.role });
    setErrorMessage(null);
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);

    startTransition(async () => {
      if (onSave) {
        const res = await onSave(formData, editingUser?.id);
        if (res.isFailure()) {
          setErrorMessage(res.unwrapError() ?? "Operation failed");
          return;
        }
      }

      // Optimistic local update
      if (editingUser) {
        setUsers((prev) =>
          prev.map((u) => (u.id === editingUser.id ? { ...u, ...formData } : u))
        );
      } else {
        const newUser: UserItem = {
          id: Date.now(),
          ...formData,
          createdAt: new Date().toISOString().split("T")[0],
        };
        setUsers((prev) => [newUser, ...prev]);
      }
      setIsModalOpen(false);
    });
  };

  const handleDelete = (id: number) => {
    if (!window.confirm("Are you sure you want to delete this record?")) return;
    startTransition(async () => {
      if (onDelete) {
        const res = await onDelete(id);
        if (res.isFailure()) {
          setErrorMessage(res.unwrapError() ?? "Delete failed");
          return;
        }
      }
      setUsers((prev) => prev.filter((u) => u.id !== id));
    });
  };

  return (
    <div className="space-y-6 max-w-6xl mx-auto p-4 font-sans text-zinc-900 dark:text-zinc-100">
      {/* Header & Controls */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Resource Management</h1>
          <p className="text-sm text-zinc-500">React 19 + TypeScript + Tailwind CSS data table.</p>
        </div>
        <div className="flex items-center gap-3 w-full sm:w-auto">
          <input
            type="search"
            placeholder="Search records..."
            aria-label="Search records"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
            className="w-full sm:w-64 px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <button
            type="button"
            onClick={handleOpenCreate}
            className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition"
          >
            + Create
          </button>
        </div>
      </div>

      {/* Error Alert */}
      {errorMessage && (
        <div role="alert" className="p-3 text-sm rounded-lg bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800">
          {errorMessage}
        </div>
      )}

      {/* Accessible Data Table */}
      <div className="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
        <table className="w-full text-left text-sm border-collapse">
          <thead className="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50 text-zinc-600 dark:text-zinc-400 font-medium">
            <tr>
              <th
                scope="col"
                onClick={() => {
                  setSortField("name");
                  setSortAsc(!sortAsc);
                }}
                className="py-3 px-4 cursor-pointer hover:text-zinc-900 dark:hover:text-zinc-100 select-none"
              >
                Name {sortField === "name" && (sortAsc ? "↑" : "↓")}
              </th>
              <th scope="col" className="py-3 px-4">Email</th>
              <th scope="col" className="py-3 px-4">Role</th>
              <th scope="col" className="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800">
            {paginatedUsers.length === 0 ? (
              <tr>
                <td colSpan={4} className="py-8 text-center text-zinc-500">
                  No records match your criteria.
                </td>
              </tr>
            ) : (
              paginatedUsers.map((user) => (
                <tr key={user.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                  <td className="py-3 px-4 font-medium">{user.name}</td>
                  <td className="py-3 px-4 text-zinc-600 dark:text-zinc-400">{user.email}</td>
                  <td className="py-3 px-4">
                    <span
                      className={`inline-block px-2 py-0.5 text-xs font-medium rounded-full ${
                        user.role === "admin"
                          ? "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300"
                          : "bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                      }`}
                    >
                      {user.role}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-right space-x-2">
                    <button
                      type="button"
                      onClick={() => handleOpenEdit(user)}
                      className="px-2.5 py-1 text-xs font-medium rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                    >
                      Edit
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(user.id)}
                      className="px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded transition"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>

        {/* Pagination */}
        <div className="flex items-center justify-between p-3 border-t border-zinc-200 dark:border-zinc-800 text-xs text-zinc-500">
          <span>Page {page} of {totalPages}</span>
          <div className="flex gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
              className="px-3 py-1 rounded border border-zinc-300 dark:border-zinc-700 disabled:opacity-40"
            >
              Previous
            </button>
            <button
              type="button"
              disabled={page >= totalPages}
              onClick={() => setPage((p) => p + 1)}
              className="px-3 py-1 rounded border border-zinc-300 dark:border-zinc-700 disabled:opacity-40"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      {/* Accessible Dialog Modal */}
      {isModalOpen && (
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="modal-title"
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        >
          <div className="w-full max-w-md p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xl space-y-4">
            <h2 id="modal-title" className="text-lg font-semibold">
              {editingUser ? "Edit User Record" : "Create New User"}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label htmlFor="user-name" className="block text-xs font-medium mb-1">Name</label>
                <input
                  id="user-name"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label htmlFor="user-email" className="block text-xs font-medium mb-1">Email</label>
                <input
                  id="user-email"
                  type="email"
                  required
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label htmlFor="user-role" className="block text-xs font-medium mb-1">Role</label>
                <select
                  id="user-role"
                  value={formData.role}
                  onChange={(e) => setFormData({ ...formData, role: e.target.value as UserFormData["role"] })}
                  className="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-transparent focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="member">Member</option>
                  <option value="admin">Admin</option>
                  <option value="viewer">Viewer</option>
                </select>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isPending}
                  className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition"
                >
                  {isPending ? "Saving..." : "Save"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
