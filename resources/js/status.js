export const STATUS = {
    normal: { label: "Normal", cls: "bg-gray-100 text-gray-600" },
    wali_kelas: {
        label: "Pengawasan wali kelas",
        cls: "bg-blue-100 text-blue-700",
    },
    bk: { label: "Pengawasan guru BK", cls: "bg-yellow-100 text-yellow-800" },
    sp1: { label: "SP 1", cls: "bg-red-100 text-red-700" },
    sp2: { label: "SP 2", cls: "bg-red-100 text-red-700" },
    sp3: { label: "SP 3", cls: "bg-red-100 text-red-700" },
    dropout: { label: "Dropout", cls: "bg-gray-900 text-white" },
};

export const ACTIONS = [
    { value: "wali_kelas", label: "Wali kelas" },
    { value: "bk", label: "Guru BK" },
    { value: "sp1", label: "SP 1" },
    { value: "sp2", label: "SP 2" },
    { value: "sp3", label: "SP 3" },
    { value: "dropout", label: "Dropout" },
];

export const fmt = (d) =>
    new Date(d).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });

export const keterangan = (s) => {
    if (s.status === "dropout") {
        const h = s.handlings.find((h) => h.type === "dropout");
        return h ? `Di-dropout pada ${fmt(h.created_at)}` : "Di-dropout";
    }
    if (s.status === "bk") {
        if (s.bk_handled) return `Sudah ditangani ${s.bk?.name ?? "guru BK"}`;
        return s.bk ? `Dirujuk ke ${s.bk.name}` : "Belum ada guru BK tujuan";
    }
    if (s.bk_handled) return "Pernah ditangani guru BK";
    return "";
};