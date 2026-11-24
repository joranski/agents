/**
 * Immutable Railway Oriented Programming (ROP) Result wrapper in TypeScript.
 *
 * Construct with Result.success(data) or Result.failure(error).
 * Chain pipeline steps with then(); chains short-circuit on the first failure.
 */
export class Result<T, E = Error | string> {
  private constructor(
    private readonly _isSuccess: boolean,
    private readonly _value?: T,
    private readonly _error?: E
  ) {}

  public static success<T, E = Error | string>(data: T): Result<T, E> {
    return new Result<T, E>(true, data, undefined);
  }

  public static failure<T = unknown, E = Error | string>(error: E): Result<T, E> {
    return new Result<T, E>(false, undefined, error);
  }

  public isSuccess(): boolean {
    return this._isSuccess;
  }

  public isFailure(): boolean {
    return !this._isSuccess;
  }

  public unwrap(): T {
    if (!this._isSuccess) {
      const message = this._error instanceof Error ? this._error.message : String(this._error);
      throw new Error(`Cannot unwrap failure Result: ${message}`);
    }
    return this._value as T;
  }

  public unwrapError(): E | undefined {
    return this._isSuccess ? undefined : this._error;
  }

  public then<U>(next: (value: T) => Result<U, E>): Result<U, E> {
    if (!this._isSuccess) {
      return Result.failure<U, E>(this._error as E);
    }
    return next(this._value as T);
  }
}
