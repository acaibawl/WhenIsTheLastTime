/**
 * カテゴリーアイコンの型定義と定数
 * イベントのカテゴリーを表すアイコンの定義を一元管理
 */

/** カテゴリーアイコンの型 */
export type CategoryType
  = 'pin'
    | 'book'
    | 'folder'
    | 'star'
    | 'chart'
    | 'sun'
    | 'person'
    | 'hospital'
    | 'medical'
    | 'leaf'
    | 'search'
    | 'people'
    | 'snowflake'
    | 'fire'
    | 'lightning';

/** カテゴリーアイコン詳細情報 */
export interface CategoryIcon {
  id: CategoryType;
  icon: string;
  label: string;
  color: string;
}

/**
 * カテゴリーアイコンの詳細リスト
 * アイコン選択UIで使用
 */
export const CATEGORY_ICONS: CategoryIcon[] = [
  { id: 'pin', icon: '📌', label: 'ピン', color: '#EF4444' },
  { id: 'book', icon: '📚', label: '本', color: '#3B82F6' },
  { id: 'folder', icon: '📁', label: 'フォルダ', color: '#F59E0B' },
  { id: 'star', icon: '⭐', label: 'スター', color: '#EAB308' },
  { id: 'chart', icon: '📊', label: 'グラフ', color: '#8B5CF6' },
  { id: 'sun', icon: '☀️', label: '太陽', color: '#F97316' },
  { id: 'person', icon: '👤', label: '人物', color: '#6B7280' },
  { id: 'hospital', icon: '🏥', label: '病院', color: '#EC4899' },
  { id: 'medical', icon: '➕', label: 'プラス・医療', color: '#10B981' },
  { id: 'leaf', icon: '🍃', label: '葉', color: '#22C55E' },
  { id: 'search', icon: '🔍', label: '虫眼鏡', color: '#6366F1' },
  { id: 'people', icon: '👥', label: '複数人', color: '#8B5CF6' },
  { id: 'snowflake', icon: '❄️', label: '雪の結晶', color: '#06B6D4' },
  { id: 'fire', icon: '🔥', label: '炎', color: '#DC2626' },
  { id: 'lightning', icon: '⚡', label: '雷', color: '#EACC0B' },
];

/**
 * カテゴリーIDからアイコン絵文字へのマッピング
 * イベント一覧表示などで使用
 */
export const CATEGORY_ICON_MAP: Record<CategoryType, string> = {
  pin: '📌',
  book: '📚',
  folder: '📁',
  star: '⭐',
  chart: '📊',
  sun: '☀️',
  person: '👤',
  hospital: '🏥',
  medical: '➕',
  leaf: '🍃',
  search: '🔍',
  people: '👥',
  snowflake: '❄️',
  fire: '🔥',
  lightning: '⚡',
};

/**
 * カテゴリーIDから絵文字を取得するヘルパー関数
 * @param categoryIcon カテゴリーID
 * @returns 絵文字 (デフォルト: 📌)
 */
export const getCategoryIcon = (categoryIcon: CategoryType): string => {
  return CATEGORY_ICON_MAP[categoryIcon] || '📌';
};
