// 共通型定義: Event, History, Statistics, GroupedHistory
// 他の型も必要に応じてここに追加してください
import type { CategoryType } from '~/constants/categories';

export interface Event {
  id: number;
  name: string;
  categoryIcon: CategoryType;
  lastExecutedHistoryId: number | null;
  lastExecutedAt: string | null;
  lastExecutedMemo: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface History {
  id: number;
  eventId: number;
  executedAt: string;
  memo?: string;
  createdAt: string;
  updatedAt: string;
}

export interface Statistics {
  thisWeek: number;
  thisMonth: number;
  total: number;
  averageInterval: string;
  averageDays: number;
}

export interface GroupedHistory {
  yearMonth: string;
  histories: History[];
}
